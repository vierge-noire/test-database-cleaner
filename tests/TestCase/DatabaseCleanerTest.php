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

namespace ViergeNoirePHPUnitListener\Test\TestCase;


use PHPUnit\Framework\TestCase;
use ViergeNoirePHPUnitListener\Connection\AbstractConnection;
use ViergeNoirePHPUnitListener\DatabaseCleaner;
use ViergeNoirePHPUnitListener\Test\Util\TestUtil;

class DatabaseCleanerTest extends TestCase
{
    /**
     * @var DatabaseCleaner
     */
    public $databaseCleaner;

    /**
     * @var AbstractConnection
     */
    public $testConnection;

    public function setUp()
    {
        $this->databaseCleaner = new DatabaseCleaner(TestUtil::getConnectionManager());
        $this->testConnection = $this->databaseCleaner->getSniffer('test')->getConnection();
    }

    public function tearDown()
    {
        unset($this->databaseCleaner);
        unset($this->testConnection);
    }

    public function testGetActiveConnections()
    {
        $this->assertSame(
            ['test'],
            $this->databaseCleaner->getActiveConnections()
        );
    }

    public function iterator()
    {
        return [[0], [1], [2]];
    }

    /**
     * @dataProvider iterator
     */
    public function testTruncateDirtyTables(int $i)
    {
        $countryName = 'Foo' . rand(1, 10000);
        TestUtil::insertCountry($this->testConnection, $countryName);

        $countries = $this->testConnection->fetchList("SELECT name from countries");
        $this->assertSame([$countryName], $countries);
        $cities = $this->testConnection->fetchList("SELECT name from cities");
        $this->assertSame([], $cities);
    }
}