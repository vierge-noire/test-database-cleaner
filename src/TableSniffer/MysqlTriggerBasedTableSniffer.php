<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         1.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace ViergeNoirePHPUnitListener\TableSniffer;

class MysqlTriggerBasedTableSniffer extends BaseTableSniffer implements TriggerBasedTableSnifferInterface
{
    /**
     * @inheritDoc
     */
    public function truncateDirtyTables()
    {
        $this->getConnection()->execute('CALL TruncateDirtyTables();');
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->getConnection()->fetchList("
            SELECT table_name
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE();
        ", 'table_name');
    }

    /**
     * @inheritDoc
     */
    public function dropTables(array $tables)
    {
        $this->removeDirtyTableCollectorFromArray($tables);

        if (empty($tables)) {
            return;
        }
        $this->getConnection()->execute('SET FOREIGN_KEY_CHECKS=0;');

        $stmt = 'SET FOREIGN_KEY_CHECKS=0;';

        $stmt .= $this->implodeSpecial(
            'DROP TABLE IF EXISTS `',
            $tables,
            '`;'
        );
        $stmt .= 'TRUNCATE TABLE `' . self::DIRTY_TABLE_COLLECTOR . '`;';

        $stmt .= 'SET FOREIGN_KEY_CHECKS=1;';

        $this->getConnection()->execute($stmt);


    }

    /**
     * @inheritDoc
     */
    public function createTriggers()
    {
        // drop triggers
        $this->dropTriggers();

        $dirtyTable = self::DIRTY_TABLE_COLLECTOR;
        $triggerPrefix = self::TRIGGER_PREFIX;

        $stmts = "";
        foreach ($this->getAllTablesExceptMigrations() as $table) {
            if ($table === $dirtyTable) {
                continue;
            }
            $stmts .= "            
            CREATE TRIGGER {$triggerPrefix}{$table} AFTER INSERT ON `{$table}`
            FOR EACH ROW                
                INSERT IGNORE INTO {$dirtyTable} (table_name) VALUES ('{$table}'), ('{$dirtyTable}');                
            ";
        }

        if ($stmts !== '') {
            $this->getConnection()->execute($stmts);
        }
    }

    /**
     * @inheritDoc
     */
    public function setup()
    {
        parent::setup();

        // create dirty tables collector
        $this->createDirtyTableCollector();

        // create triggers
        $this->createTriggers();

        $dirtyTable = self::DIRTY_TABLE_COLLECTOR;

        // create truncate procedure
        $this->getConnection()->execute("DROP PROCEDURE IF EXISTS TruncateDirtyTables;");
        $this->getConnection()->execute("
            DROP PROCEDURE IF EXISTS TruncateDirtyTables;
            CREATE PROCEDURE TruncateDirtyTables()
            BEGIN
                DECLARE current_table_name VARCHAR(128);
                DECLARE finished INTEGER DEFAULT 0;
                DECLARE dirty_table_cursor CURSOR FOR
                    SELECT dt.table_name FROM {$dirtyTable} dt
                    INNER JOIN information_schema.TABLES info_schema on dt.table_name = info_schema.TABLE_NAME
                    WHERE info_schema.table_schema = DATABASE();                    
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
            
                SET FOREIGN_KEY_CHECKS=0;
                OPEN dirty_table_cursor;
                truncate_tables: LOOP
                    FETCH dirty_table_cursor INTO current_table_name;
                    IF finished = 1 THEN
                        LEAVE truncate_tables;
                    END IF;
                    SET @create_trigger_statement = CONCAT('TRUNCATE TABLE `', current_table_name, '`;');
                    PREPARE stmt FROM @create_trigger_statement;
                    EXECUTE stmt;
                    DEALLOCATE PREPARE stmt;
                END LOOP truncate_tables;
                CLOSE dirty_table_cursor;
                            
                SET FOREIGN_KEY_CHECKS=1;
            END
        ");
    }

    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggers = $this->getConnection()->fetchList("SHOW triggers", 'Trigger');

        foreach ($triggers as $k => $trigger) {
            if (strpos($trigger, self::TRIGGER_PREFIX) !== 0) {
                unset($triggers[$k]);
            }
        }

        return (array)$triggers;
    }

    /**
     * @inheritDoc
     */
    public function dropTriggers()
    {
        $triggers = $this->getTriggers();
        if (empty($triggers)) {
            return;
        }

        $stmts = $this->implodeSpecial(
            "DROP TRIGGER ",
            $triggers,
            ";"
        );
        $this->getConnection()->execute($stmts);
    }
}