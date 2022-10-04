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
namespace TestDatabaseCleaner;

use TestDatabaseCleaner\Error\PDOErrorException;
use TestDatabaseCleaner\Sniffer\BaseTableSniffer;

/**
 * Connection cleaner
 */
final class ConnectionCleaner
{
    /**
     * @var \TestDatabaseCleaner\Sniffer\BaseTableSniffer
     */
    private $sniffer;

    /**
     * @param \TestDatabaseCleaner\Sniffer\BaseTableSniffer $sniffer Table Sniffer
     */
    public function __construct(BaseTableSniffer $sniffer)
    {
        $this->sniffer = $sniffer;
    }

    /**
     * Truncate all the dirty tables
     *
     * @return void
     * @throws \TestDatabaseCleaner\Error\PDOErrorException if the query failed
     */
    public function truncateDirtyTables(): void
    {
        try {
            $this->sniffer->truncateDirtyTables();
        } catch (PDOErrorException $e) {
            //            // The dirty table collector might not be found because the session
            //            // was interrupted.
            $this->init();
            try {
                $this->sniffer->truncateDirtyTables();
            } catch (\Throwable $e) {
                throw new PDOErrorException($e->getMessage());
            }
        }
    }

    /**
     * @return \TestDatabaseCleaner\Sniffer\BaseTableSniffer
     */
    public function getSniffer(): BaseTableSniffer
    {
        return $this->sniffer;
    }

    /**
     * Get the sniffer started
     * Typically create the dirty table collector
     * Truncate all tables
     * Create the spying triggers
     *
     * @return void
     */
    protected function init(): void
    {
        if ($this->dirtyTableCollectorExists()) {
            return;
        }

        try {
            $this->shutdown();
            $this->sniffer->createDirtyTableCollector();
            $this->createTriggers();
            $this->sniffer->createTruncateDirtyTablesProcedure();
            $this->sniffer->markAllTablesToCleanAsDirty();
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $message .= ' ----- Please truncate your test schema manually and run the test suite again.';
            throw new \RuntimeException($message);
        }
    }

    /**
     * Stop spying
     *
     * @return void
     */
    protected function shutdown(): void
    {
        $this->sniffer->dropTriggers();
        $this->sniffer->dropDirtyTableCollector();
    }

    /**
     * Check that the dirty table collector exists
     *
     * @return bool
     */
    protected function dirtyTableCollectorExists(): bool
    {
        return in_array(BaseTableSniffer::DIRTY_TABLE_COLLECTOR_NAME, $this->sniffer->getAllTables(true));
    }

    /**
     * Create triggers for all non ignored tables
     *
     * @return void
     */
    protected function createTriggers(): void
    {
        foreach ($this->sniffer->getAllNonIgnoredTables(true) as $table) {
            $triggerName = $this->getTriggerName($table);
            $this->sniffer->createTriggerFor($triggerName, $table);
        }
    }

    /**
     * The length of the trigger name is limited to 64 due to MySQL constrain.
     *
     * @param  string $tableName Name of the table to create a trigger on.
     * @return string
     */
    protected function getTriggerName(string $tableName): string
    {
        return substr(BaseTableSniffer::TRIGGER_PREFIX . $tableName, 0, 64);
    }
}
