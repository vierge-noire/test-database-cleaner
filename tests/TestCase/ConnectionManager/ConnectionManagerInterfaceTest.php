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

namespace ViergeNoirePHPUnitListener\Test\TestCase\ConnectionManager;


use ViergeNoirePHPUnitListener\Connection\CakePHPConnection;
use ViergeNoirePHPUnitListener\TableSniffer\MysqlTriggerBasedTableSniffer;
use ViergeNoirePHPUnitListener\Test\Util\TestCase;

class ConnectionManagerInterfaceTest extends TestCase
{
    public function testGetAbstractConnectionClassName()
    {
        if ($this->isRunningOnCakePHP()) {
            $expect = CakePHPConnection::class;
        }
        $this->assertSame($expect, $this->connectionManager->getAbstractConnectionClassName());
    }

    public function testSkipConnection()
    {
        $this->assertSame(false, $this->connectionManager->skipConnection('test'));
        $this->assertSame(true, $this->connectionManager->skipConnection('test_dummy'));
    }

    public function testGetConnectionSnifferClass()
    {
        $this->assertSame('', $this->connectionManager->getConnectionSnifferClass('test'));
    }

    public function testGetDriver()
    {
        $expect =  getenv('DB_DRIVER');
        $this->assertSame($expect, $this->connectionManager->getDriver('test'));
    }

    public function testGetTestConnections()
    {
        $expect =  ['test'];
        $this->assertArraysHaveSameContent($expect, $this->connectionManager->getTestConnections());
    }
}