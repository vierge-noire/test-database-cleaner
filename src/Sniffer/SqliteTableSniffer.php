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
namespace TestDatabaseCleaner\Sniffer;

use TestDatabaseCleaner\Error\PDOErrorException;

class SqliteTableSniffer extends BaseTableSniffer
{
    /**
     * @inheritDoc
     */
    public function truncateDirtyTables(): void
    {
        $tables = $this->getDirtyTables();

        if (empty($tables)) {
            return;
        }

        // If a dirty table got dropped, it should be ignored
        $tables = array_intersect($tables, $this->getAllTables(true));

        if (empty($tables)) {
            return;
        }

        $this->execute('PRAGMA foreign_keys = OFF');

        try {
            foreach ($tables as $table) {
                $this->execute('DELETE FROM ' . $table);
                try {
                    $this->execute('DELETE FROM sqlite_sequence WHERE name = ' . $table);
                } catch (\PDOException | PDOErrorException $e) {
                }
            }
        } finally {
            $this->execute('PRAGMA foreign_keys = ON');
        }

        $this->execute('DELETE FROM ' . self::DIRTY_TABLE_COLLECTOR_NAME);
    }

    /**
     * @inheritDoc
     */
    public function createTriggerFor(string $trigger, string $table): void
    {
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;
        $this->execute("
            CREATE TRIGGER {$trigger} AFTER INSERT ON `$table` 
                BEGIN
                    INSERT OR IGNORE INTO {$collectorName} VALUES ('{$table}');
                END;
        ");
    }

    /**
     * @inheritDoc
     */
    public function createTruncateDirtyTablesProcedure(): void
    {
        // Do nothing, as Sqlite does not support procedures
    }

    /**
     * @inheritDoc
     */
    public function markAllTablesToCleanAsDirty(): void
    {
        $tables = $this->getAllNonIgnoredTables();
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;

        $stmt = "INSERT OR IGNORE INTO {$collectorName} VALUES ('" . implode("'), ('", $tables) . "')";
        $this->execute($stmt);
    }

    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggerPrefix = self::TRIGGER_PREFIX;

        return $this->fetchQuery(
            "SELECT name FROM sqlite_master WHERE type = 'trigger' AND name LIKE '{$triggerPrefix}%'"
        );
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

        foreach ($triggers as $trigger) {
            $this->execute("DROP TRIGGER {$trigger};");
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->fetchQuery('
            SELECT name FROM sqlite_master WHERE type="table" AND name != "sqlite_sequence"
        ');
    }
}
