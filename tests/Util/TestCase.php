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

namespace ViergeNoirePHPUnitListener\Test\Util;


use Cake\Datasource\ConnectionManager;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Artisan;
use Migrations\Migrations;
use ViergeNoirePHPUnitListener\Connection\AbstractConnection;
use ViergeNoirePHPUnitListener\ConnectionManager\CakePHPConnectionManager;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;
use ViergeNoirePHPUnitListener\ConnectionManager\LaravelConnectionManager;
use ViergeNoirePHPUnitListener\DatabaseCleaner;
use ViergeNoirePHPUnitListener\TableSniffer\BaseTableSniffer;
use ViergeNoirePHPUnitListener\Test\Traits\ArrayComparerTrait;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use ArrayComparerTrait;

    /**
     * @var DatabaseCleaner
     */
    public $databaseCleaner;

    /**
     * @var ConnectionManagerInterface
     */
    public $connectionManager;

    /**
     * @var AbstractConnection
     */
    public $testConnection;

    /**
     * @var BaseTableSniffer
     */
    public $testSniffer;

    /**
     * @var string
     */
    public $testConnectionName;

    public function setUp()
    {
        $this->databaseCleaner      = new DatabaseCleaner(TestUtil::getConnectionManager());
        $this->connectionManager    = $this->databaseCleaner->getConnectionManager();
        $this->testConnectionName   = $this->isRunningOnCakePHP() ? 'test' : 'default';
        $this->testSniffer          = $this->databaseCleaner->getSniffer($this->testConnectionName);
        $this->testConnection       = $this->testSniffer->getConnection();
    }

    public function tearDown()
    {
        unset($this->databaseCleaner);
        unset($this->connectionManager);
        unset($this->testConnectionName);
        unset($this->testSniffer);
        unset($this->testConnection);
    }

    public function driverIs(string $driver): bool
    {
        return $this->connectionManager->getDriver($this->testConnectionName) === $driver;
    }

    public function activateForeignKeysOnSqlite() {
        if ($this->connectionManager->getDriver($this->testConnectionName) === DatabaseCleaner::SQLITE_DRIVER) {
            $this->testConnection->execute('PRAGMA foreign_keys = ON;' );
        }
    }

    public function insert(string $table, string $name = 'Foo')
    {
        $this->testConnection->execute("INSERT INTO {$table} (name) VALUES ('{$name}');");
    }

    public function insertCountry(string $name = 'Foo')
    {
        $this->insert('countries', $name);
    }

    public function insertCity(string $name = 'FooCity')
    {
        $countryId = rand(1, 100000);
        $countryName = $name . 'Country';
        $this->testConnection->execute("INSERT INTO countries (id, name) VALUES ('{$countryId}', '{$countryName}');");
        $this->testConnection->execute("INSERT INTO cities (name, country_id) VALUES ('{$name}', '{$countryId}');");
    }

    public function isRunningOnCakePHP(): bool
    {
        return ($this->connectionManager instanceof CakePHPConnectionManager);
    }

    public function isRunningOnLaravel(): bool
    {
        return ($this->connectionManager instanceof LaravelConnectionManager);
    }

    public function runMigrations()
    {
        if ($this->isRunningOnCakePHP()) {
            return $this->runCakePHPMigrations();
        }
        if ($this->isRunningOnLaravel()) {
            return $this->runLaravelMigrations();
        }
    }

    public function runCakePHPMigrations()
    {
        $config = [
            'connection' => 'test',
            'source' => 'TestMigrations',
        ];

        $migrations = new Migrations($config);
        $migrations->migrate($config);

        return $migrations;
    }

    public function runLaravelMigrations()
    {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/products',
            '--force' => true
        ]);
    }

    public function rollbackMigrations($migrations)
    {
        if ($this->isRunningOnCakePHP()) {
            return $this->rollbackCakePHPMigrations($migrations);
        }
        if ($this->isRunningOnLaravel()) {
            return $this->rollbackLaravelMigrations($migrations);
        }
    }

    public function rollbackCakePHPMigrations(Migrations $migrations)
    {
        $migrations->rollback();
    }

    public function rollbackLaravelMigrations()
    {
        Artisan::call('migrate:rollback', [
            '--path'    => 'database/migrations/products',
            '--force'   => true,
            '--step'    => 1,
        ]);
    }

    public function createNonExistentConnection(string $name)
    {
        if ($this->isRunningOnCakePHP()) {
            return $this->createNonExistentCakePHPConnection($name);
        }
        if ($this->isRunningOnLaravel()) {
            return $this->createNonExistentLaravelConnection($name);
        }
    }

    public function createNonExistentCakePHPConnection(string $name)
    {
        $config = ConnectionManager::getConfig('test');
        $config['database'] = 'dummy_database';
        ConnectionManager::setConfig($name, $config);
    }

    public function createNonExistentLaravelConnection(string $name)
    {
        $baseConfig = [
            "driver" => "mysql",
            "host" =>"Mysql",
            "database" => "dummy_database",
            "username" => "root",
            "password" => "root"
        ];
        $capsule = new Manager();
        $capsule->addConnection($baseConfig);
    }

    public function getMigrationTableName()
    {
        if ($this->isRunningOnCakePHP()) {
            return 'phinxlog';
        }
        if ($this->isRunningOnLaravel()) {
            return 'migrations';
        }
    }
}