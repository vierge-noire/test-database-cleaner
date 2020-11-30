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
use ViergeNoirePHPUnitListener\Connection\LaravelConnection;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;
use ViergeNoirePHPUnitListener\Test\Util\TestCase;
use ViergeNoirePHPUnitListener\Test\Util\TestUtil;

class ConnectionManagerInterfaceTest extends TestCase
{
    public function testGetAbstractConnectionClassName()
    {
        if ($this->isRunningOnCakePHP()) {
            $expect = CakePHPConnection::class;
        }
        if ($this->isRunningOnLaravel()) {
            $expect = LaravelConnection::class;
        }
        $this->assertSame($expect, $this->connectionManager->getAbstractConnectionClassName());
    }

    public function testSkipConnection()
    {
        $this->assertSame(false, $this->connectionManager->skipConnection([
            ConnectionManagerInterface::SNIFFER_CONFIG_KEY => true,
        ]));
        $this->assertSame(true, $this->connectionManager->skipConnection([
            'random_key' => true,
        ]));
    }

    public function testGetConnectionSnifferClass()
    {
        $this->assertSame(TestUtil::getSnifferClassName(), $this->connectionManager->getConnectionSnifferClass($this->testConnectionName));
    }

    public function testGetDriver()
    {
        $expect =  getenv('DB_DRIVER');
        $this->assertSame($expect, $this->connectionManager->getDriver($this->testConnectionName));
    }

    public function testGetTestConnections()
    {
        $expect =  [$this->testConnectionName];
        $this->assertArraysHaveSameContent($expect, $this->connectionManager->getTestConnections());
    }
}