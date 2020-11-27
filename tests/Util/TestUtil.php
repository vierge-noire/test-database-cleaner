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


use Cake\Datasource\ConnectionManager;
use Migrations\Migrations;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

class TestUtil
{
    static public function getConnectionManager(): ConnectionManagerInterface
    {
        $managerName = 'ViergeNoirePHPUnitListener\ConnectionManager\\' .  FRAMEWORK . 'ConnectionManager';

        return new $managerName();
    }

    public static function makeUuid(): string
    {
        return '123e4567-e89b-12d3-a456-' . rand(100000000000, 999999999999);
    }
}