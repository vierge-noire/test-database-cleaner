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

namespace ViergeNoirePHPUnitListener\Test\TestCase\Connection;


use ViergeNoirePHPUnitListener\Test\Util\TestCase;

class AbstractConnectionTest extends TestCase
{
    public function testFilterMigrationTables()
    {
        $nonMigrationsTable = [
            'Da', 'doo', 'dAh'
        ];
        $allTables = $nonMigrationsTable;

        if ($this->isRunningOnCakePHP()) {
            $allTables[] = 'phinxlog';
            $allTables[] = 'phinxlog_some_plugin';
        }

        $this->assertSame($nonMigrationsTable, $this->testConnection->filterMigrationTables($allTables));
    }

    public function testGetConnectionName()
    {
        if ($this->isRunningOnCakePHP()) {
            $expect = 'test';
        }
        $this->assertSame($expect, $this->testConnection->getConnectionName());
    }

    public function testFetchList()
    {
        $countries = [
            'Country 1',
            'Country 2',
            'Country 3',
        ];
        foreach ($countries as $country) {
            $this->insert('countries', $country);
        }
        $act = $this->testConnection->fetchList("SELECT name FROM countries");
        $this->assertSame($countries, $act);
    }
}