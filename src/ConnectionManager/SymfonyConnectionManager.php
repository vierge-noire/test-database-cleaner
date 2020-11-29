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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Illuminate\Database\Capsule\Manager;
use ViergeNoirePHPUnitListener\Connection\SymfonyConnection;
use ViergeNoirePHPUnitListener\DatabaseCleaner;

class SymfonyConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var Connection
     */
    public $em;

    public function initialize(): void
    {
        $baseConfig = [
            'driver' => 'pdo_mysql',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'test_phpunit_listener',
            self::SKIP_CONNECTION_CONFIG_KEY => false,
        ];


//        $this->em = DriverManager::getConnection($baseConfig);

//        $config = Setup::createAnnotationMetadataConfiguration($paths, true);
//        $this->em = EntityManager::create($baseConfig, $config);
    }

    public function getConnection(): Connection
    {
        return $this->em;
    }

    public function getAbstractConnectionClassName(): string
    {
        return SymfonyConnection::class;
    }

    public function getTestConnections(): array
    {
        return [
            'test'
        ];
    }

    public function skipConnection(string $connectionName): bool
    {
        $ignoreConnection = $this->em->getParams()[self::SKIP_CONNECTION_CONFIG_KEY] ?? false;
        return $ignoreConnection === true;
    }

    public function getConnectionSnifferClass(string $connectionName): string
    {
        return $this->em->getParams()[self::SNIFFER_CONFIG_KEY] ?? '';
    }

    public function getDriver(string $connectionName): string
    {
        $driver = $this->em->getDriver()->getName();

        $map = [
            'pdo_sqlite'    => DatabaseCleaner::SQLITE_DRIVER,
            'pdo_mysql'     => DatabaseCleaner::MYSQL_DRIVER,
            'pdo_pgsql'     => DatabaseCleaner::POSTGRES_DRIVER,
        ];

        $mappedDriver = $map[$driver] ?? null;

        if (is_null($mappedDriver)) {
            throw new \RuntimeException("The driver $driver is not supported.");
        } else {
            return $mappedDriver;
        }
    }
}