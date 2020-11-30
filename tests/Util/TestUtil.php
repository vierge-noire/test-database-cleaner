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

namespace ViergeNoirePHPUnitListener\Test\Util;

use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

class TestUtil
{
    public static function getConnectionManager(): ConnectionManagerInterface
    {
        $managerName = 'ViergeNoirePHPUnitListener\ConnectionManager\\' .  getenv('FRAMEWORK') . 'ConnectionManager';

        return new $managerName();
    }

    public static function getSnifferClassName()
    {
        $driver = getenv('DB_DRIVER');
        return "\ViergeNoirePHPUnitListener\TableSniffer\\".$driver ."TriggerBasedTableSniffer";
    }

    public static function makeUuid(): string
    {
        return '123e4567-e89b-12d3-a456-' . rand(100000000000, 999999999999);
    }
}