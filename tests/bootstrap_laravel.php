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

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

if (!getenv('FRAMEWORK')) {
    putenv('FRAMEWORK=Laravel');
}

require_once 'tests/bootstap.php';

$app = require_once ROOT . '/tests/Util/laravel_app/bootstrap/app.php';

/** @var Config $config */
$config = $app->get(Config::class);
$config::setFacadeApplication($app);


$driver = strtolower(getenv('DB_DRIVER'));
if ($driver === 'postgres') {
    $driver = 'pgsql';
}

putenv("LARAVEL_DRIVER=$driver");

$capsule = $app->get(Capsule::class);

//Make this Capsule instance available globally.
$capsule->setAsGlobal();

//// Setup the Eloquent ORM.
$capsule->bootEloquent();

Artisan::call('migrate', ['--force' => true]);
