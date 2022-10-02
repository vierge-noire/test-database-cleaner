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

use PDO;
use TestDatabaseCleaner\Error\ConfigurationErrorException;
use TestDatabaseCleaner\Error\DriverNotSupportedException;
use TestDatabaseCleaner\Sniffer\MysqlTableSniffer;
use TestDatabaseCleaner\Sniffer\PostgresTableSniffer;
use TestDatabaseCleaner\Sniffer\SqliteTableSniffer;

/**
 * Connection registry
 */
final class ConnectionRegistry
{
    /**
     * Configuration sets.
     *
     * @var array<array-key, \TestDatabaseCleaner\ConnectionCleaner>
     */
    private static $connections = [];

    /**
     * Adds a connection to the registry
     *
     * @param  string   $alias         Connection alias
     * @param \PDO $pdo PDO
     * @param  string[] $ignoredTables Tables that should not be truncated (e.g. migrations tables)
     * @return void
     * @throws \TestDatabaseCleaner\Error\ConfigurationErrorException if the alias is already defined
     * @throws \TestDatabaseCleaner\Error\DriverNotSupportedException if the driver is not supported
     */
    public static function addConnection(string $alias, PDO $pdo, array $ignoredTables = []): void
    {
        if (isset(self::$connections[$alias])) {
            throw new ConfigurationErrorException(sprintf('Cannot reconfigure existing key "%s"', $alias));
        }

        self::$connections[$alias] = self::getConnectionCleaner($pdo, $ignoredTables);
    }

    /**
     * @return array<array-key, \TestDatabaseCleaner\ConnectionCleaner>
     */
    public static function getConnections(): array
    {
        return self::$connections;
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::$connections = [];
    }

    /**
     * @param \PDO $pdo Connection
     * @param  string[] $ignoredTables Tables that should not be truncated (e.g. migrations tables)
     * @return \TestDatabaseCleaner\ConnectionCleaner Connection cleaner
     * @throws \TestDatabaseCleaner\Error\DriverNotSupportedException if the driver is not supported
     */
    private static function getConnectionCleaner(PDO $pdo, array $ignoredTables): ConnectionCleaner
    {
        /** @var string $driverName */
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driverName) {
            case 'mysql':
                $sniffer = new MysqlTableSniffer($pdo, $ignoredTables);
                break;
            case 'sqlite':
                $sniffer = new SqliteTableSniffer($pdo, $ignoredTables);
                break;
            case 'postgres':
            case 'postgres':
            case 'pgsql':
                $sniffer = new PostgresTableSniffer($pdo, $ignoredTables);
                break;
            default:
                throw new DriverNotSupportedException("The driver $driverName is not supported");
        }

        return new ConnectionCleaner($sniffer);
    }
}
