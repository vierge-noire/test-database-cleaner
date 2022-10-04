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
use TestDatabaseCleaner\ConnectionRegistry;
use TestDatabaseCleaner\Error\ConfigurationErrorException;
use TestDatabaseCleaner\Sniffer\MysqlTableSniffer;
use TestDatabaseCleaner\Sniffer\PostgresTableSniffer;
use TestDatabaseCleaner\Sniffer\SqliteTableSniffer;

class ConnectionRegistryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        ConnectionRegistry::clear();
    }

    /**
     * @return string[][]
     */
    public function dataForValidSniffers(): array
    {
        return [
            ['mysql', MysqlTableSniffer::class],
            ['sqlite', SqliteTableSniffer::class],
            ['postgres', PostgresTableSniffer::class],
        ];
    }

    /**
     * @dataProvider dataForValidSniffers
     * @param string $pdoAttribute
     * @param class-string<\TestDatabaseCleaner\Sniffer\BaseTableSniffer> $expectedSnifferInstance
     */
    public function testConnectionRegistry_AddConnection_Valid(string $pdoAttribute, string $expectedSnifferInstance): void
    {
        $stub = $this->createMock(\PDO::class);
        $stub->method('getAttribute')->willReturn($pdoAttribute);
        ConnectionRegistry::addConnection('test', $stub);

        $connections = ConnectionRegistry::getConnections();
        $this->assertInstanceOf($expectedSnifferInstance, $connections['test']->getSniffer());
    }

    public function testConnectionRegistry_AddConnection_Alias_Already_Defined(): void
    {
        $stub = $this->createMock(\PDO::class);
        $stub->method('getAttribute')->willReturn('mysql');
        ConnectionRegistry::addConnection('test', $stub);

        $this->expectException(ConfigurationErrorException::class);
        ConnectionRegistry::addConnection('test', $stub);
    }
}
