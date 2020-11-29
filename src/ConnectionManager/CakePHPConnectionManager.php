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


use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use ViergeNoirePHPUnitListener\Connection\CakePHPConnection;

class CakePHPConnectionManager implements ConnectionManagerInterface
{
    public function initialize(): void
    {
        $this->aliasConnections();
    }

    public function getAbstractConnectionClassName(): string
    {
        return CakePHPConnection::class;
    }

    public function getTestConnections(): array
    {
        $connections = [];
        foreach (ConnectionManager::configured() as $i => $connectionName) {
            if (!$this->skipConnection($connectionName)) {
                $connections[] = $connectionName;
            }
        }
        return $connections;
    }

    public function skipConnection(string $connectionName): bool
    {
        $ignoredConnections = Configure::read('PHPUnitIgnoredConnections', []);
        // CakePHP 4 solves a DebugKit issue by creating an Sqlite connection
        // in tests/bootstrap.php. This connection should be ignored.
        if ($connectionName === 'test_debug_kit' || in_array($connectionName, $ignoredConnections)) {
            return true;
        }

        if ((ConnectionManager::getConfig($connectionName)[self::SKIP_CONNECTION_CONFIG_KEY] ?? false) === true) {
            return true;
        }

        if ($connectionName === 'test' || strpos($connectionName, 'test_') === 0) {
            return false;
        }

        return true;
    }

    public function getConnectionSnifferClass(string $connectionName): string
    {
        $config = ConnectionManager::getConfig($connectionName);
        return $config[self::SNIFFER_CONFIG_KEY] ?? '';
    }

    public function getDriver(string $connectionName): string
    {
        $driver = ConnectionManager::get($connectionName)->config()['driver'];
        $cast = explode('\\', $driver);
        return $cast[array_key_last($cast)];
    }

    /**
     * Directly copied from Cake\TestSuite\Fixture\FixtureManager
     *
     * Add aliases for all non test prefixed connections.
     *
     * This allows models to use the test connections without
     * a pile of configuration work.
     *
     * @return void
     */
    protected function aliasConnections()
    {
        $connections = ConnectionManager::configured();
        ConnectionManager::alias('test', 'default');
        $map = [];
        foreach ($connections as $connection) {
            if ($connection === 'test' || $connection === 'default') {
                continue;
            }
            if (isset($map[$connection])) {
                continue;
            }
            if (strpos($connection, 'test_') === 0) {
                $map[$connection] = substr($connection, 5);
            } else {
                $map['test_' . $connection] = $connection;
            }
        }
        foreach ($map as $testConnection => $normal) {
            ConnectionManager::alias($testConnection, $normal);
        }
    }
}