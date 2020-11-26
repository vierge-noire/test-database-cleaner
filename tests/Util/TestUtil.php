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
use Migrations\Migrations;
use ViergeNoirePHPUnitListener\Connection\AbstractConnection;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

class TestUtil
{
    static public function getConnectionManager(): ConnectionManagerInterface
    {
        $managerName = 'ViergeNoirePHPUnitListener\ConnectionManager\\' .  FRAMEWORK . 'ConnectionManager';

        return new $managerName();
    }

    public static function makeUuid(): string
    {
        return '123e4567-e89b-12d3-a456-' . rand(100000000000, 999999999999);
    }

    public static function insertCountry(AbstractConnection $conn, string $name = 'FooCountry')
    {
        $conn->execute("INSERT INTO countries (name) VALUES ('{$name}');");
    }

    public static function insertCity(AbstractConnection $conn, string $name = 'FooCity')
    {
        $countryId = rand(1, 100000);
        $conn->execute("INSERT INTO countries (id, name) VALUES ('{$countryId}', '{$name}');");
        $conn->execute("INSERT INTO cities (name, country_id) VALUES ('{$name}', '{$countryId}');");
    }

    public static function runMigrations()
    {
        switch (FRAMEWORK) {
            case 'CakePHP':
                return self::runCakePHPMigrations();
        }
    }

    public static function runCakePHPMigrations()
    {
        $config = [
            'connection' => 'test',
            'source' => 'TestMigrations',
        ];

        $migrations = new Migrations($config);
        $migrations->migrate($config);

        return $migrations;
    }

    public static function rollbackMigrations($migrations)
    {
        switch (FRAMEWORK) {
            case 'CakePHP':
                return self::rollbackCakePHPMigrations($migrations);
        }
    }

    public static function rollbackCakePHPMigrations(Migrations $migrations)
    {
        $migrations->rollback();
    }

    public static function createNonExistentConnection(string $name)
    {
        switch (FRAMEWORK) {
            case 'CakePHP':
                return self::createNonExistentCakePHPConnection($name);
        }
    }

    public static function createNonExistentCakePHPConnection(string $name)
    {
        $config = ConnectionManager::getConfig('test');
        $config['database'] = 'dummy_database';
        ConnectionManager::setConfig($name, $config);
    }
}