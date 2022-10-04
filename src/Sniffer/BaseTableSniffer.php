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

use PDO;
use PDOStatement;
use TestDatabaseCleaner\Error\PDOErrorException;

abstract class BaseTableSniffer
{
    /**
     * The name of the table where all the dirty tables are collected
     */
    public const DIRTY_TABLE_COLLECTOR_NAME = 'test_database_cleaner_dirty_tables';

    /**
     * The prefix added to the name of the trigger
     */
    public const TRIGGER_PREFIX = 'dts_';

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string[]|null
     */
    protected $allTables;

    /**
     * @var string[]
     */
    protected $ignoredTables;

    /**
     * Truncate all the dirty tables
     *
     * @return void
     * @throws \TestDatabaseCleaner\Error\PDOErrorException if the query failed
     */
    abstract public function truncateDirtyTables(): void;

    /**
     * Query the DB to get a list of all tables
     *
     * @return string[]
     */
    abstract public function fetchAllTables(): array;

    /**
     * Create trigger for a given table listening to inserts on all tables
     *
     * @param string $trigger The name of the trigger
     * @param string $table The table targeted
     * @return void
     */
    abstract public function createTriggerFor(string $trigger, string $table): void;

    /**
     * Drop triggers relative to the database dirty table collector
     *
     * @return void
     */
    abstract public function dropTriggers(): void;

    /**
     * Get a list of triggers relative to the database dirty table collector
     *
     * @return string[]
     */
    abstract public function getTriggers(): array;

    /**
     * Create the procedure truncating the dirty tables.
     *
     * @return void
     */
    abstract public function createTruncateDirtyTablesProcedure(): void;

    /**
     * Mark all tables except those skipped and dirty table collector as dirty.
     *
     * @return void
     */
    abstract public function markAllTablesToCleanAsDirty(): void;

    /**
     * @param \PDO $pdo PDO
     * @param string[] $ignoredTables Tables to ignore between tests (e.g. migrations tables)
     */
    public function __construct(PDO $pdo, array $ignoredTables)
    {
        $this->pdo = $pdo;
        $this->ignoredTables = array_merge_recursive($ignoredTables, [self::DIRTY_TABLE_COLLECTOR_NAME]);
    }

    /**
     * Find all tables where an insert happened
     * This also includes empty tables, where a delete action
     * was performed after an insert.
     *
     * @return string[]
     */
    public function getDirtyTables(): array
    {
        return $this->fetchQuery('SELECT table_name FROM ' . self::DIRTY_TABLE_COLLECTOR_NAME);
    }

    /**
     * @param  string $cmd Command to execute
     * @return \PDOStatement
     * @throws \TestDatabaseCleaner\Error\PDOErrorException if the query returned false
     * @throws \PDOException if the query failed
     */
    protected function execute(string $cmd): PDOStatement
    {
        $result = $this->pdo->query($cmd);

        if ($result === false) {
            throw new PDOErrorException("The following command failed: $cmd");
        }

        return $result;
    }

    /**
     * @param  bool $forceFetch Force to query the DB
     * @return string[]
     */
    public function getAllTables(bool $forceFetch = false): array
    {
        if (is_null($this->allTables) || $forceFetch) {
            $this->allTables = $this->fetchAllTables();
        }

        return $this->allTables;
    }

    /**
     * Create the table gathering the dirty tables.
     *
     * @return void
     */
    public function createDirtyTableCollector(): void
    {
        $collector = self::DIRTY_TABLE_COLLECTOR_NAME;
        $this->execute("CREATE TABLE IF NOT EXISTS {$collector} (table_name VARCHAR(128) PRIMARY KEY);");
    }

    /**
     * Fetch all tables, excluded from the list to ignore and the dirty table collector.
     *
     * @param  bool $forceFetch Force to query the DB
     * @return string[]
     */
    public function getAllNonIgnoredTables(bool $forceFetch = false): array
    {
        $allTables = $this->getAllTables($forceFetch);

        return array_diff($allTables, $this->ignoredTables);
    }

    /**
     * Drop the table gathering the dirty tables
     *
     * @return void
     */
    public function dropDirtyTableCollector()
    {
        $this->execute('DROP TABLE IF EXISTS ' . self::DIRTY_TABLE_COLLECTOR_NAME);
    }

    /**
     * Execute a query returning a list of table or triggers
     * In case where the query fails because the database queried does
     * not exist, an exception is thrown.
     *
     * @param  string $query Query to fetch
     * @return string[]
     * @throws \TestDatabaseCleaner\Error\PDOErrorException
     */
    protected function fetchQuery(string $query): array
    {
        $tables = $this->execute($query)->fetchAll();
        if ($tables == false) {
            return [];
        }
        foreach ($tables as $i => $val) {
            $tables[$i] = $val[0] ?? $val['name'];
        }

        return $tables;
    }
}
