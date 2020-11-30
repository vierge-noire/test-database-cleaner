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

use Cake\Datasource\ConnectionManager;
use Migrations\Migrations;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

if (!getenv('FRAMEWORK')) {
    putenv('FRAMEWORK=CakePHP');
}

require_once 'tests/bootstap.php';

define('APP_DIR', 'src');
define('APP_PATH', ROOT . DS . 'TestApp' . DS);
define('VENDOR_PATH', ROOT . DS . 'vendor' . DS);

define('CAKE_CORE_INCLUDE_PATH', VENDOR_PATH . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('CORE_TESTS', ROOT . DS . 'tests' . DS);
define('CORE_TEST_CASES', CORE_TESTS . 'TestCase');
define('TEST_APP', CORE_TESTS . 'Util' . DS . 'cakephp_app' . DS);

// Point app constants to the test app.
define('CONFIG', TEST_APP . 'config' . DS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$dbConnection = [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\\' . getenv('DB_DRIVER'),
    'persistent' => false,
    'host' => getenv('DB_HOST'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PWD'),
    'database' => getenv('DB_DATABASE'),
    'encoding' => 'utf8',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
    'quoteIdentifiers' => true,
    'log' => false,
];

// This connection is meant to be ignored
$dummyConnection = $dbConnection;
$dummyConnection['driver'] = 'Foo';
ConnectionManager::setConfig('test_dummy', $dummyConnection);

// Create the default connection, to be ignored too
ConnectionManager::setConfig('default', $dbConnection);

// Set the sniffer, indicating that the connection should be truncated and how
$dbConnection[ConnectionManagerInterface::SNIFFER_CONFIG_KEY] = \ViergeNoirePHPUnitListener\Test\Util\TestUtil::getSnifferClassName();
ConnectionManager::setConfig('test', $dbConnection);

$migrations = new Migrations(['connection' => 'test']);
$migrations->migrate();