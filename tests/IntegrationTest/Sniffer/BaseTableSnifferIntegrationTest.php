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
namespace TestDataBaseCleaner\Test\IntegrationTest\Sniffer;

use PDO;
use PHPUnit\Framework\TestCase;
use TestDataBaseCleaner\Sniffer\BaseTableSniffer;

class BaseTableSnifferIntegrationTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $PDO;

    /**
     * @var BaseTableSniffer
     */
    protected $sniffer;

    public function setUp(): void
    {
        try {
            $dns = TEST_DNS;
            $this->PDO = new PDO($dns);
        } catch (\PDOException $e) {
            $this->fail("Connection with DNS $dns failed. " . $e->getMessage());
        }
        $this->sniffer = $this->getSniffer();
        $this->createTables();
        $this->sniffer->createDirtyTableCollector();
        $this->sniffer->dropTriggers();
        $this->PDO->exec('DELETE FROM ' . BaseTableSniffer::DIRTY_TABLE_COLLECTOR_NAME);
        foreach ($this->getTableNames() as $table) {
            $this->PDO->exec('DELETE FROM ' . $table);
        }
    }

    /**
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::getDirtyTables
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::fetchQuery
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::execute
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::markAllTablesToCleanAsDirty
     */
    public function testBaseTableSniffer_getDirtyTables(): void
    {
        $sniffer = $this->sniffer;
        $this->assertEmpty($sniffer->getDirtyTables());
        $sniffer->markAllTablesToCleanAsDirty();
        $this->assertSame($this->getTableNames(), $sniffer->getDirtyTables());
    }

    /**
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::getAllNonIgnoredTables
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::getAllTables
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::fetchAllTables
     */
    public function testBaseTableSniffer_getAllNonIgnoredTables(): void
    {
        $notIgnoredTables = [$this->getTableNames()[0]];
        $ignoredTables = [$this->getTableNames()[1]];
        $sniffer = $this->getSniffer($ignoredTables);

        $result = $sniffer->getAllNonIgnoredTables();
        $this->assertEquals($notIgnoredTables, $result);
    }

    /**
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::dropTriggers
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::getTriggers
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::createTriggerFor
     */
    public function testBaseTableSniffer_CreateTriggerFor(): void
    {
        $sniffer = $this->sniffer;
        $sniffer->dropTriggers();
        $this->assertEmpty($sniffer->getTriggers());

        $table = $this->getTableNames()[0];
        $triggerName = BaseTableSniffer::TRIGGER_PREFIX . $table;
        $sniffer->createTriggerFor($triggerName, $table);
        $this->assertSame([$triggerName], $sniffer->getTriggers());

        $sniffer->dropTriggers();
        $this->assertEmpty($sniffer->getTriggers());
    }

    /**
     * @covers \TestDataBaseCleaner\Sniffer\BaseTableSniffer::createTruncateDirtyTablesProcedure
     */
    public function testBaseTableSniffer_CreateTruncateDirtyTablesProcedure(): void
    {
        $sniffer = $this->sniffer;
        $tables = $this->getTableNames();

        foreach ($tables as $table) {
            $sniffer->createTriggerFor(BaseTableSniffer::TRIGGER_PREFIX . $table, $table);
        }
        $sniffer->createTruncateDirtyTablesProcedure();

        foreach ($tables as $table) {
            $this->insertInTableValue($table, 'Foo');
            /** @var \PDOStatement $query */
            $query = $this->PDO->query('SELECT COUNT(*) c FROM ' . $table);
            /** @var string[] $count */
            $count = $query->fetch();
            $this->assertEquals(1, $count['c']);
        }

        $this->assertEquals($this->getTableNames(), $sniffer->getDirtyTables());
        $sniffer->truncateDirtyTables();

        foreach ($tables as $table) {
            /** @var \PDOStatement $query */
            $query = $this->PDO->query('select count(*) c from ' . $table);
            /** @var string[] $count */
            $count = $query->fetch();
            $this->assertEquals(0, $count['c']);
        }
    }

    /**
     * @param string[] $ignoredTables
     * @return BaseTableSniffer
     */
    protected function getSniffer(array $ignoredTables = []): BaseTableSniffer
    {
        /** @var string $driver */
        $driver = getenv('DB_DRIVER');
        $snifferName = 'TestDataBaseCleaner\Sniffer\\' . ucfirst($driver) . 'TableSniffer';

        /** @var BaseTableSniffer $sniffer */
        $sniffer = new $snifferName($this->PDO, $ignoredTables);

        return $sniffer;
    }

    /**
     * @return string[]
     */
    protected function createTables(): array
    {
        $tables = $this->getTableNames();
        foreach ($tables as $table) {
            $this->PDO->exec(
                "CREATE TABLE IF NOT EXISTS {$table} (name VARCHAR(128) PRIMARY KEY);"
            );
        }

        return $tables;
    }

    /**
     * @return string[]
     */
    protected function getTableNames(): array
    {
        return [
            'table1', 'table2',
        ];
    }

    /**
     * @param string $table
     * @param string $value
     */
    protected function insertInTableValue(string $table, string $value): void
    {
        $this->PDO->exec("INSERT INTO {$table} VALUES ('" . $value . "')");
    }
}
