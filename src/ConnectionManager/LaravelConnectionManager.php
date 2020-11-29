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

namespace ViergeNoirePHPUnitListener\ConnectionManager;

use Illuminate\Database\Capsule\Manager;
use ViergeNoirePHPUnitListener\Connection\LaravelConnection;
use ViergeNoirePHPUnitListener\DatabaseCleaner;

class LaravelConnectionManager implements ConnectionManagerInterface
{
    public function initialize(): void
    {}

    public function getAbstractConnectionClassName(): string
    {
        return LaravelConnection::class;
    }

    public function getTestConnections(): array
    {
        return [
            'test'
        ];
    }

    public function skipConnection(string $connectionName): bool
    {
        return (Manager::connection($connectionName)->getConfig(self::SKIP_CONNECTION_CONFIG_KEY) === true);
    }

    public function getConnectionSnifferClass(string $connectionName): string
    {
        return Manager::connection($connectionName)->getConfig(self::SNIFFER_CONFIG_KEY) ?? '';
    }

    public function getDriver(string $connectionName): string
    {
        $driver = Manager::connection($connectionName)->getDriverName();

        $map = [
            'sqlite'    => DatabaseCleaner::SQLITE_DRIVER,
            'mysql'     => DatabaseCleaner::MYSQL_DRIVER,
            'pgsql'     => DatabaseCleaner::POSTGRES_DRIVER,
        ];

        $mappedDriver = $map[$driver] ?? null;

        if (is_null($mappedDriver)) {
            throw new \RuntimeException("The driver $driver is not supported.");
        } else {
            return $mappedDriver;
        }
    }
}