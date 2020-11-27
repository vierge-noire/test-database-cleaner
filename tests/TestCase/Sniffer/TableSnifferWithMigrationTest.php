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

class TableSnifferWithMigrationTest extends TestCase
{
    public $migrations;

    public function setUp()
    {
        parent::setUp();
        $this->migrations = $this->runMigrations();
    }

    public function tearDown(): void
    {
        $this->rollbackMigrations($this->migrations);
        $this->rollbackMigrations($this->migrations);
        unset($this->migrations);

        // Parent Tear down as last!!!
        parent::tearDown();
    }

    protected function countProducts(): int
    {
        return (int) $nProducts = $this->testConnection->fetchList("SELECT COUNT(*) as c FROM products", 'c')[0];
    }

    /**
     * Find dirty tables
     * Since the table products was created
     * after the setup of the sniffer triggers,
     * it is not marked as dirty
     */
    public function testPopulateWithMigrationsWithoutSetup()
    {
        $tables = $this->testSniffer->fetchAllTables();
        $this->assertTrue(in_array('products', $tables));
        $this->assertSame([], $this->testSniffer->getDirtyTables());
    }

    public function testPopulateWithMigrationsWithSetup()
    {
        $tables = $this->testSniffer->fetchAllTables();
        $this->assertTrue(in_array('products', $tables));

        // Rollback the table products population migration
        $this->rollbackMigrations($this->migrations);

        $expected = [
            'dirty_table_spy_countries',
            'dirty_table_spy_cities',
        ];
        if (!($this->testSniffer instanceof MysqlTriggerBasedTableSniffer)) {
            $expected[] = 'dirty_table_spy_' . TriggerBasedTableSnifferInterface::DIRTY_TABLE_COLLECTOR;
        }

        $this->assertArraysHaveSameContent($expected, $this->testSniffer->getTriggers());

        // Reset the triggers
        $this->testSniffer->setup();
        $expected[] = 'dirty_table_spy_products';
        $nProducts = $this->countProducts();

        // Populate the products table
        $this->runMigrations();

        $this->assertArraysHaveSameContent($expected, $this->testSniffer->getTriggers());

        // Assert that a product was created
        $this->assertSame($nProducts + 1, $this->countProducts());

        // Assert that the products table is marked dirty
        $this->assertContains('products', $this->testSniffer->getDirtyTables());
    }
}