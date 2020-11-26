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
namespace ViergeNoirePHPUnitListener\Test\DropTablesTestCase;

use PHPUnit\Framework\TestCase;
use ViergeNoirePHPUnitListener\Connection\AbstractConnection;
use ViergeNoirePHPUnitListener\DatabaseCleaner;
use ViergeNoirePHPUnitListener\TableSniffer\BaseTableSniffer;
use ViergeNoirePHPUnitListener\Test\Util\TestUtil;

class TableSnifferDropCitiesTest extends TestCase
{
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
        $this->databaseCleaner = new DatabaseCleaner(TestUtil::getConnectionManager());
        $this->testConnection = $this->databaseCleaner->getSniffer('test')->getConnection();
    }

    public function tearDown()
    {
        unset($this->databaseCleaner);
        unset($this->testConnection);
    }

    private function activateForeignKeysOnSqlite() {
        if ($this->databaseCleaner->getConnectionManager()->getDriver('test') === DatabaseCleaner::SQLITE_DRIVER) {
            $this->testConnection->execute('PRAGMA foreign_keys = ON;' );
        }
    }

    public function testDropWithForeignKeyCheckCities()
    {
        $this->activateForeignKeysOnSqlite();
        TestUtil::insertCity($this->testConnection);
        $this->databaseCleaner->dropTables('test');

        $this->expectException(\PDOException::class);
        TestUtil::insertCity($this->testConnection);
    }
}