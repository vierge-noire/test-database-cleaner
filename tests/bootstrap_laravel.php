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

require_once "vendor/autoload.php";

use Illuminate\Database\Capsule\Manager as Capsule;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

putenv('FRAMEWORK=Laravel');

require_once 'tests/bootstap.php';

$driver = strtolower(getenv('DB_DRIVER'));
if ($driver === 'postgres') {
    $driver = 'pgsql';
}

$baseConfig = [
    "driver" => $driver,
    "host" => getenv('DB_HOST'),
    "database" => getenv('DB_DATABASE'),
    "username" => getenv('DB_USER'),
    "password" => getenv('DB_PWD'),
];

$capsule = new Capsule;
$capsule->addConnection($baseConfig, 'test');

$dummyConnection = $baseConfig;
$dummyConnection['driver'] = 'mysql';
$dummyConnection[ConnectionManagerInterface::SKIP_CONNECTION_CONFIG_KEY] = true;
$capsule->addConnection($dummyConnection, 'test_dummy');

//Make this Capsule instance available globally.
$capsule->setAsGlobal();

// Setup the Eloquent ORM.
$capsule->bootEloquent();

$capsule->bootEloquent();

require_once "tests/bootstrap_cakephp.php";


//\Illuminate\Support\Facades\Artisan::call('migrate', array('--path' => 'tests/TestApp/config/Migrations', '--force' => true));
