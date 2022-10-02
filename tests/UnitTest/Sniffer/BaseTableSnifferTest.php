<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2020 Vierge Noire Development
 * @link      https://github.com/vierge-noire/test-database-cleaner
 * @since     1.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestDataBaseCleaner\Test\UnitTest\Sniffer;

use PHPUnit\Framework\TestCase;
use TestDataBaseCleaner\Sniffer\BaseTableSniffer;

class BaseTableSnifferTest extends TestCase
{
    public function testBaseTableSniffer_getAllNonIgnoredTables(): void
    {
        $stub = $this->createMock(\PDO::class);
        $notIgnoredTables = ['not_ignored_1', 'not_ignored_2'];
        $ignoredTables = ['ignored_1', 'ignored_2'];
        $allTables = array_merge($notIgnoredTables, $ignoredTables, [BaseTableSniffer::DIRTY_TABLE_COLLECTOR_NAME]);
        $sniffer = $this->getMockForAbstractClass(BaseTableSniffer::class, [$stub, $ignoredTables]);
        $sniffer->method('fetchAllTables')->willReturn($allTables);
        $sniffer->expects($this->once())->method('fetchAllTables');

        $result = $sniffer->getAllNonIgnoredTables();
        $this->assertEquals($notIgnoredTables, $result);
    }
}
