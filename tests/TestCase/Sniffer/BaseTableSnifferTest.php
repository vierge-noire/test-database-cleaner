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
namespace ViergeNoirePHPUnitListener\Test\TestCase\Sniffer;

use ViergeNoirePHPUnitListener\TableSniffer\MysqlTriggerBasedTableSniffer;
use ViergeNoirePHPUnitListener\TableSniffer\TriggerBasedTableSnifferInterface;
use ViergeNoirePHPUnitListener\Test\Util\TestCase;

class BaseTableSnifferTest extends TestCase
{
    public function dataProviderOfDirtyTables()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * If a DB is not created, the sniffers should throw an exception
     */
    public function testGetSnifferOnNonExistentDB()
    {
        if ($this->driverIs('Sqlite')) {
            $this->assertTrue(true);
        } else {
            $this->expectException(\Exception::class);
        }

        $connectionName = 'test_dummy_connection';
        $this->createNonExistentConnection($connectionName);

        $this->databaseCleaner->getSniffer($connectionName);
    }

    public function testImplodeSpecial()
    {
        $array = ['foo', 'bar'];
        $glueBefore = 'ABC';
        $glueAfter = 'DEF';
        $expect = 'ABCfooDEFABCbarDEF';
        $this->assertSame($expect, $this->testSniffer->implodeSpecial($glueBefore, $array, $glueAfter));
    }

    public function testCheckTriggersAfterSetup()
    {
        $expected = [
            'dirty_table_spy_cities',
            'dirty_table_spy_countries',
        ];
        if ($this->driverIs('Mysql')) {
            $found = $this->testConnection->fetchList('SHOW TRIGGERS', 'Trigger');
        } elseif ($this->driverIs('Postgres')) {
            $found = $this->testConnection->fetchList('SELECT tgname FROM pg_trigger', 'tgname');
            $expected[] = 'dirty_table_spy_' . TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR;
        } elseif ($this->driverIs('Sqlite')) {
            $found = $this->testConnection->fetchList('SELECT name FROM sqlite_master WHERE type = "trigger"', 'name');
            $expected[] = 'dirty_table_spy_' . TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR;
        }

        foreach ($expected as $trigger) {
            $this->assertSame(true, in_array($trigger, $found), "Trigger $trigger was not found");
        }
    }

    public function testGetAllTablesExceptMigrations()
    {
        $found = $this->testSniffer->getAllTablesExceptMigrations();
        $expected = [
            'cities',
            'countries',
            TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR
        ];

        $this->assertArraysHaveSameContent($expected, $found);
    }

    /**
     * Find dirty tables
     * Countries is dirty, Cities is empty
     */
    public function testGetDirtyTables()
    {
        $expected = [
            'countries',
            TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR,
        ];

        $this->insertCountry();
        $found = $this->testSniffer->getDirtyTables();
        $this->assertArraysHaveSameContent($expected, $found);
    }

    /**
     * This list will need to be maintained as new tables are created or removed
     */
    public function testGetAllTables()
    {
        $found = $this->testSniffer->fetchAllTables();
        $expected = [
            'cities',
            'countries',
            $this->getMigrationTableName(),
            TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR
        ];

        $this->assertArraysHaveSameContent($expected, $found);
    }

    /**
     * Given: A city with a country
     * When: Country gets deleted
     * Then: Throw an error
     */
    public function testThatForeignKeysConstrainWorksOnDelete()
    {
        $this->expectException(\PDOException::class);
        $this->insertCity();
        $this->testConnection->execute('DELETE FROM countries');
    }

    public function testTruncateWithForeignKey()
    {
        $this->insertCity();

        $this->testSniffer->truncateDirtyTables();

        $count = (int)$this->testConnection->fetchList('select count(*) as c from cities, countries', 'c')[0];

        $this->assertSame(0, $count);
    }

    public function testGetAndDropTriggers()
    {
        $found = $this->testSniffer->getTriggers();
        $expected = [
            'dirty_table_spy_countries',
            'dirty_table_spy_cities',
        ];
        if (!($this->testSniffer instanceof MysqlTriggerBasedTableSniffer)) {
            $expected[] = 'dirty_table_spy_' . TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR;
        }

        $this->assertArraysHaveSameContent($expected, $found);

        $this->testSniffer->dropTriggers();
        $expected = [];
        $found = $this->testSniffer->getTriggers();
        $this->assertArraysHaveSameContent($expected, $found);

        $this->testSniffer->setup();
    }
}