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

namespace ViergeNoirePHPUnitListener\ConnectionManager;


interface ConnectionManagerInterface
{
    const SKIP_CONNECTION_CONFIG_KEY = 'skipInTestSuite';
    const SNIFFER_CONFIG_KEY         = 'tableSniffer';

    public function getAbstractConnectionClassName(): string;

    public function skipConnection(string $connectionName): bool;

    public function getConnectionSnifferClass(string $connectionName): string;

    /**
     * The name of the driver used. These should be
     *
     * @param string $connectionName
     * @return string
     */
    public function getDriver(string $connectionName): string;

    public function getTestConnections(): array;

    /**
     * Framework specific task to perform before the test suite starts
     * It is allowed to perform nothing.
     */
    public function initialize(): void;
}