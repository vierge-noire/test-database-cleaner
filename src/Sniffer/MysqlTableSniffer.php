<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2020 Vierge Noire Development
 * @link      https://github.com/vierge-noire/test-database-cleaner
 * @since     1.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestDataBaseCleaner\Sniffer;

class MysqlTableSniffer extends BaseTableSniffer
{
    /**
     * @inheritDoc
     */
    public function truncateDirtyTables(): void
    {
        $this->execute('CALL TruncateDirtyTables();');
    }

    /**
     * @inheritDoc
     */
    public function createTriggerFor(string $trigger, string $table): void
    {
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;
        $this->execute("    
            CREATE TRIGGER {$trigger} AFTER INSERT ON `{$table}`
            FOR EACH ROW                
                INSERT IGNORE INTO {$collectorName} VALUES ('{$table}');                
        ");
    }

    /**
     * @inheritDoc
     */
    public function createTruncateDirtyTablesProcedure(): void
    {
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;
        $this->execute("
            DROP PROCEDURE IF EXISTS TruncateDirtyTables;
            CREATE PROCEDURE TruncateDirtyTables()
            BEGIN
                DECLARE current_table_name VARCHAR(128);
                DECLARE finished INTEGER DEFAULT 0;
                DECLARE dirty_table_cursor CURSOR FOR
                    SELECT dt.table_name FROM (
                        SELECT * FROM {$collectorName}
                        UNION
                        SELECT '{$collectorName}'
                    ) dt
                    INNER JOIN INFORMATION_SCHEMA.TABLES info
                    ON info.table_name = dt.table_name;                    
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
    public function markAllTablesToCleanAsDirty(): void
    {
        $tables = $this->getAllNonIgnoredTables();
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;
        $this->execute(
            "INSERT IGNORE INTO {$collectorName} VALUES ('" . implode("'), ('", $tables) . "')"
        );
    }

    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggers = $this->fetchQuery('SHOW triggers');
        foreach ($triggers as $k => $trigger) {
            if (strpos($trigger, self::TRIGGER_PREFIX) !== 0) {
                unset($triggers[$k]);
            }
        }

        return $triggers;
    }

    /**
     * @inheritDoc
     */
    public function dropTriggers(): void
    {
        $triggers = $this->getTriggers();
        if (empty($triggers)) {
            return;
        }

        $stmts = $this->implodeSpecial('DROP TRIGGER ', $triggers, ';');
        $this->execute($stmts);
    }

    /**
     * @param string $glueBefore String to prepend
     * @param string[]  $array Iterated array
     * @param string $glueAfter String to append
     * @return string
     */
    public function implodeSpecial(string $glueBefore, array $array, string $glueAfter): string
    {
        return $glueBefore . implode($glueAfter . $glueBefore, $array) . $glueAfter;
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->fetchQuery('SHOW TABLES;');
    }
}
