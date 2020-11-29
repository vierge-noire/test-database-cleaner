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

namespace ViergeNoirePHPUnitListener;

use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;
use ViergeNoirePHPUnitListener\TableSniffer\BaseTableSniffer;
use ViergeNoirePHPUnitListener\TableSniffer\MysqlTriggerBasedTableSniffer;
use ViergeNoirePHPUnitListener\TableSniffer\PostgresTriggerBasedTableSniffer;
use ViergeNoirePHPUnitListener\TableSniffer\SqliteTriggerBasedTableSniffer;

class DatabaseCleaner
{
    /**
     * @var ConnectionManagerInterface
     */
    public $connectionManager;

    /**
     * @var array
     */
    protected $activeConnections;

    /**
     * @var array
     */
    private $sniffers = [];

    const MYSQL_DRIVER    = 'Mysql';
    const SQLITE_DRIVER   = 'Sqlite';
    const POSTGRES_DRIVER = 'Postgres';

    public function __construct(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
        $this->connectionManager->initialize();
    }

    /**
     * @return ConnectionManagerInterface
     */
    public function getConnectionManager(): ConnectionManagerInterface
    {
        return $this->connectionManager;
    }

    /**
     * If not yet set, fetch the active connections
     * Those are the connections that are neither ignored,
     * nor irrelevant (debug_kit, non-test DBs etc...)
     * @return array
     */
    public function getActiveConnections(): array
    {
        return $this->activeConnections ?? $this->fetchActiveConnections();
    }

    /**
     * Truncate all dirty tables
     * @return void
     */
    public function truncateDirtyTables(): void
    {
        foreach ($this->getActiveConnections() as $connection) {
            $this->getSniffer($connection)->truncateDirtyTables();
        }
    }

    /**
     * Get the appropriate sniffer and drop all tables
     * @param string $connectionName
     * @return void
     */
    public function dropTables(string $connectionName): void
    {
        $this->getSniffer($connectionName)->dropTables(
            $this->getSniffer($connectionName)->fetchAllTables()
        );
    }

    /**
     * Initialize all connections used by the cleaner
     * @return array
     */
    protected function fetchActiveConnections(): array
    {
        return $this->activeConnections = $this->getConnectionManager()->getTestConnections();
    }

    /**
     * Each connection has it's own sniffer
     *
     * @param string $connectionName
     * @return BaseTableSniffer
     */
    public function getSniffer(string $connectionName): BaseTableSniffer
    {
        return $this->sniffers[$connectionName] ?? $this->addSniffer($connectionName);
    }

    /**
     * @param string $connectionName
     * @return BaseTableSniffer
     */
    public function addSniffer(string $connectionName): BaseTableSniffer
    {
        // Check if a particular sniffer is specified for this connection
        $snifferName = $this->getConnectionManager()->getConnectionSnifferClass($connectionName);

        // If not, get the default Sniffer based on the connection's driver
        $driver = '';
        if (empty($snifferName)) {
            try {
                $driver = $this->getConnectionManager()->getDriver($connectionName);
                $snifferName = $this->getDriverSnifferName($driver);
            } catch (\RuntimeException $e) {
                $msg = "The driver {$driver} is not supported or was not found";
                throw new \RuntimeException($msg);
            }
        }

        // Create connection object for the connection manager concerned
        $abstractConnectionClass = '';
        try {
            $abstractConnectionClass = $this->getConnectionManager()->getAbstractConnectionClassName();
            $abstractConnection = new $abstractConnectionClass($connectionName);
        } catch (\RuntimeException $e) {
            $msg = "The abstract connection {$abstractConnectionClass} is not supported or was not found";
            throw new \RuntimeException($msg);
        }

        // Create a table sniffer
        try {
            $sniffer = new $snifferName($abstractConnection);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage() . " The DB table sniffer {$snifferName} is not supported or was not found";
            throw new \RuntimeException($msg);
        }

        return $this->sniffers[$connectionName] = $sniffer;
    }

    /**
     * Read in the config the sniffer to use
     * @param string $driver
     * @return string
     */
    public function getDriverSnifferName(string $driver): string
    {
        try {
            $snifferName = $this->getDefaultTableSniffers()[$driver] ?? null;
            if (is_null($snifferName)) {
                throw new \RuntimeException();
            }
        } catch (\RuntimeException $e) {
            $msg = "The DB driver {$driver} is not supported or was not found";
            throw new \RuntimeException($msg);
        }
        return $snifferName;
    }

    /**
     * Table sniffers provided by the package
     * @return array
     */
    protected function getDefaultTableSniffers(): array
    {
        return [
            self::MYSQL_DRIVER     => MysqlTriggerBasedTableSniffer::class,
            self::SQLITE_DRIVER    => SqliteTriggerBasedTableSniffer::class,
            self::POSTGRES_DRIVER  => PostgresTriggerBasedTableSniffer::class,
        ];
    }
}