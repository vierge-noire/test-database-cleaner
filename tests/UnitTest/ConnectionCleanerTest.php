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
namespace TestDatabaseCleaner\Test\UnitTest;

use PHPUnit\Framework\TestCase;
use TestDatabaseCleaner\ConnectionCleaner;
use TestDatabaseCleaner\Error\PDOErrorException;
use TestDatabaseCleaner\Sniffer\BaseTableSniffer;

class ConnectionCleanerTest extends TestCase
{
    public function testConnectionCleaner_TruncateDirtyTables_On_Non_Existing_Dirty_Table_Collector(): void
    {
        $stub = $this->createMock(BaseTableSniffer::class);
        $stub->method('truncateDirtyTables')->willReturnOnConsecutiveCalls(
            $this->throwException(new PDOErrorException()),
            null
        );
        $tables = ['foo', 'bar'];
        $stub->method('getAllNonIgnoredTables')->willReturn($tables);

        $stub->expects($this->once())->method('dropTriggers');
        $stub->expects($this->once())->method('dropDirtyTableCollector');
        $stub->expects($this->once())->method('getAllNonIgnoredTables')->with($this->equalTo(true));
        $stub->expects($this->once())->method('getAllTables')->with($this->equalTo(true));
        $stub->expects($this->once())->method('createDirtyTableCollector');
        $stub->expects($this->exactly(count($tables)))->method('createTriggerFor');
        $stub->expects($this->once())->method('createTruncateDirtyTablesProcedure');
        $stub->expects($this->once())->method('markAllTablesToCleanAsDirty');

        $cleaner = new ConnectionCleaner($stub);
        $cleaner->truncateDirtyTables();
    }

    public function testConnectionCleaner_TruncateDirtyTables_On_Existing_Dirty_Table_Collector(): void
    {
        $stub = $this->createMock(BaseTableSniffer::class);
        $stub->method('truncateDirtyTables')->willReturnOnConsecutiveCalls(
            $this->throwException(new PDOErrorException()),
            null
        );
        $stub->method('getAllTables')->willReturn(['foo', 'bar', BaseTableSniffer::DIRTY_TABLE_COLLECTOR_NAME]);

        $stub->expects($this->once())->method('getAllTables')->with($this->equalTo(true));
        $stub->expects($this->never())->method('createDirtyTableCollector');
        $stub->expects($this->never())->method('createTriggerFor');
        $stub->expects($this->never())->method('createTruncateDirtyTablesProcedure');
        $stub->expects($this->never())->method('markAllTablesToCleanAsDirty');
        $stub->expects($this->never())->method('dropTriggers');
        $stub->expects($this->never())->method('dropDirtyTableCollector');

        $cleaner = new ConnectionCleaner($stub);
        $cleaner->truncateDirtyTables();
    }
}
