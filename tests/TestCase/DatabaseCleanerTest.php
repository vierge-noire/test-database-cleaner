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

use ViergeNoirePHPUnitListener\Test\Util\TestCase;

class DatabaseCleanerTest extends TestCase
{

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
        $this->insertCountry($countryName);

        $countries = $this->testConnection->fetchList("SELECT name from countries", 'name');
        $this->assertSame([$countryName], $countries);
        $cities = $this->testConnection->fetchList("SELECT name from cities", 'name');
        $this->assertSame([], $cities);
    }
}