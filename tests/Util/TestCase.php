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


use ViergeNoirePHPUnitListener\Connection\AbstractConnection;
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
     * @var AbstractConnection
     */
    public $testConnection;

    /**
     * @var BaseTableSniffer
     */
    public $testSniffer;

    public function setUp()
    {
        $this->databaseCleaner  = new DatabaseCleaner(TestUtil::getConnectionManager());
        $this->testSniffer      = $this->databaseCleaner->getSniffer('test');
        $this->testConnection   = $this->testSniffer->getConnection();
    }

    public function tearDown()
    {
        unset($this->databaseCleaner);
        unset($this->testSniffer);
        unset($this->testConnection);
    }

    public function driverIs(string $driver): bool
    {
        return $this->databaseCleaner->getConnectionManager()->getDriver('test') === $driver;
    }

    public function activateForeignKeysOnSqlite() {
        if ($this->databaseCleaner->getConnectionManager()->getDriver('test') === DatabaseCleaner::SQLITE_DRIVER) {
            $this->testConnection->execute('PRAGMA foreign_keys = ON;' );
        }
    }

    public function insert(string $table, string $name = 'Foo')
    {
        $this->testConnection->execute("INSERT INTO {$table} (name) VALUES ('{$name}');");
    }

    public function insertCity(string $name = 'FooCity')
    {
        $countryId = rand(1, 100000);
        $countryName = $name . 'Country';
        $this->testConnection->execute("INSERT INTO countries (id, name) VALUES ('{$countryId}', '{$countryName}');");
        $this->testConnection->execute("INSERT INTO cities (name, country_id) VALUES ('{$name}', '{$countryId}');");
    }
}