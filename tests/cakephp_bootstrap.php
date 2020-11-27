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

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');
define('APP_PATH', ROOT . DS . 'TestApp' . DS);
define('VENDOR_PATH', ROOT . DS . 'vendor' . DS);

define('CAKE_CORE_INCLUDE_PATH', VENDOR_PATH . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('CORE_TESTS', ROOT . DS . 'tests' . DS);
define('CORE_TEST_CASES', CORE_TESTS . 'TestCase');
define('TEST_APP', CORE_TESTS . 'TestApp' . DS);

// Point app constants to the test app.
define('CONFIG', TEST_APP . 'config' . DS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$loadEnv = function(string $fileName) {
    if (file_exists($fileName)) {
        $dotenv = new \josegonzalez\Dotenv\Loader($fileName);
        $dotenv->parse()
            ->putenv(true)
            ->toEnv(true)
            ->toServer(true);
    }
};

if (!getenv('DB_DRIVER')) {
    putenv('DB_DRIVER=Sqlite');
}
$driver =  getenv('DB_DRIVER');

if (!file_exists(ROOT . DS . '.env')) {
    @copy(".env.$driver", ROOT . DS . '.env');
}

/**
 * Read .env file(s).
 */
$loadEnv(ROOT . DS . '.env');

// Re-read the driver
$driver =  getenv('DB_DRIVER');
echo "Using driver $driver \n";

$dbConnection = [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\\' . $driver,
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
    //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
    'url' => env('DATABASE_TEST_URL', null),
];

if (getenv('TABLE_SNIFFER')) {
    $dbConnection['tableSniffer'] = getenv('TABLE_SNIFFER');
}

ConnectionManager::setConfig('default', $dbConnection);
ConnectionManager::setConfig('test', $dbConnection);

// This connection is meant to be ignored
$dummyConnection = $dbConnection;
$dummyConnection['driver'] = 'Foo';
$dummyConnection[ConnectionManagerInterface::SKIP_CONNECTION_CONFIG_KEY] = true;
ConnectionManager::setConfig('test_dummy', $dummyConnection);

$migrations = new Migrations(['connection' => 'test']);
$migrations->migrate();