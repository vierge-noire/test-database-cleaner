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

class SqliteTriggerBasedTableSniffer extends BaseTableSniffer implements TriggerBasedTableSnifferInterface
{
    /**
     * @inheritDoc
     */
    public function truncateDirtyTables()
    {
        $tables = $this->getDirtyTables();

        // If a dirty table got dropped, it should be ignored
        $tables = array_intersect($tables, $this->getAllTables(true));

        if (empty($tables)) {
            return;
        }

        $this->getConnection()->execute('PRAGMA foreign_keys = OFF;');
        foreach ($tables as $table) {
            $this->getConnection()->execute("DELETE FROM `" . $table . "`;");
            $this->getConnection()->execute("DELETE FROM `sqlite_sequence` WHERE name = '$table';");
        }
        $this->getConnection()->execute('PRAGMA foreign_keys = ON;');
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->getConnection()->fetchList("
             SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence';
        ");
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

        $this->getConnection()->execute('PRAGMA foreign_keys = OFF;');
        foreach ($tables as $table) {
            $this->getConnection()->execute("DROP TABLE IF EXISTS $table;");
        }
        $this->getConnection()->execute("DELETE FROM `" . self::DIRTY_TABLE_COLLECTOR . "`;");
        $this->getConnection()->execute('PRAGMA foreign_keys = ON;');
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

        $stmts = [];
        foreach ($this->getAllTablesExceptPhinxlogs() as $table) {
            $stmts[] = "
            CREATE TRIGGER {$triggerPrefix}{$table} AFTER INSERT ON `$table` 
                BEGIN
                    INSERT OR IGNORE INTO {$dirtyTable} VALUES ('$table');
                END;
            ";
        }
        foreach ($stmts as $stmt) {
            $this->getConnection()->execute($stmt);
        }
    }

    /**
     * @inheritDoc
     */
    public function setup()
    {
        parent::setup();

        $this->createDirtyTableCollector();
        $this->createTriggers();
    }

    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggerPrefix = self::TRIGGER_PREFIX;
        $triggers = $this->getConnection()->fetchList("
            SELECT name 
            FROM sqlite_master
            WHERE type = 'trigger'
            AND name LIKE '{$triggerPrefix}%'
        ");

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

        foreach ($triggers as $trigger) {
            $this->getConnection()->execute("
                DROP TRIGGER $trigger;");
        }
    }
}