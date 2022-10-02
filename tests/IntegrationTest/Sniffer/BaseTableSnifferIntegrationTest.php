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
namespace TestDatabaseCleaner\Test\IntegrationTest\Sniffer;

use PDO;
use PHPUnit\Framework\TestCase;
use TestDatabaseCleaner\Sniffer\BaseTableSniffer;
use TestDatabaseCleaner\Sniffer\MysqlTableSniffer;
use TestDatabaseCleaner\Sniffer\SqliteTableSniffer;

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
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::getDirtyTables
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::fetchQuery
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::execute
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::markAllTablesToCleanAsDirty
     */
    public function testBaseTableSniffer_getDirtyTables(): void
    {
        $sniffer = $this->sniffer;
        $this->assertEmpty($sniffer->getDirtyTables());
        $sniffer->markAllTablesToCleanAsDirty();
        $this->assertSame($this->getTableNames(), $sniffer->getDirtyTables());
    }

    /**
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::getAllNonIgnoredTables
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::getAllTables
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::fetchAllTables
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
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::dropTriggers
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::getTriggers
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::createTriggerFor
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
     * @covers \TestDatabaseCleaner\Sniffer\BaseTableSniffer::createTruncateDirtyTablesProcedure
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
            $this->insertInTableValue($table, 'name', 'Foo');
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

    public function testBaseTableSniffer_Seequence_Reset(): void
    {
        $offset = 1;
        $n = 3;
        $table = $this->getTableNames()[0];

        $populateTable = function ($table, $n) {
            for ($i = 1; $i <= $n; $i++) {
                $this->insertInTableValue($table, 'name', 'foo');
            }
        };

        // Mark the table as dirty are create a trigger so the sequence will be dropped
        $this->sniffer->createTriggerFor(BaseTableSniffer::TRIGGER_PREFIX . $table, $table);
        $populateTable($table, $n);
        $this->sniffer->truncateDirtyTables();

        // Repopulate the table and check the ids
        $populateTable($table, $n);

        $stmt = $this->PDO->query("SELECT id FROM {$table} ORDER BY id");
        if ($stmt === false) {
            $this->fail();
        }
        /** @var string[][] $entries */
        $entries = $stmt->fetchAll();
        for ($i = 0; $i < $n; $i++) {
            $this->assertEquals($offset + $i, $entries[$i][0]);
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
        $snifferName = 'TestDatabaseCleaner\Sniffer\\' . ucfirst($driver) . 'TableSniffer';

        /** @var BaseTableSniffer $sniffer */
        $sniffer = new $snifferName($this->PDO, $ignoredTables);

        return $sniffer;
    }

    /**
     * @return void
     */
    protected function createTables(): void
    {
        if ($this->sniffer instanceof MysqlTableSniffer) {
            $autoincrement = 'INTEGER PRIMARY KEY AUTO_INCREMENT';
        } elseif ($this->sniffer instanceof SqliteTableSniffer) {
            $autoincrement = ' INTEGER PRIMARY KEY AUTOINCREMENT';
        } else {
            $autoincrement = 'SERIAL PRIMARY KEY';
        }
        $tables = $this->getTableNames();
        foreach ($tables as $table) {
            $stmt = "
                CREATE TABLE IF NOT EXISTS {$table} (
                id $autoincrement,
                name VARCHAR(128))
            ";
            $this->PDO->exec($stmt);
        }
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
     * @param string $field
     * @param mixed $value
     */
    protected function insertInTableValue(string $table, string $field, $value): void
    {
        $this->PDO->exec(
            "INSERT INTO $table ($field) VALUES ('" . $value . "')"
        );
    }
}
