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

use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

if (!getenv('FRAMEWORK')) {
    putenv('FRAMEWORK=Symfony');
}

require_once 'tests/bootstap.php';

define('SYMFONY_APP_ROOT', ROOT . DS . 'tests'. DS . 'Util' . DS . 'symfony_app');

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array("/path/to/entity-files");
$isDevMode = false;

// the connection configuration
$baseConfig = [
    'driver' => 'pdo_mysql',
    'user' => 'root',
    'password' => 'root',
    'dbname' => 'test_phpunit_listener',
];



//$app = new \Symfony\Component\Console\Application('Test Symfony');

$app = new \SymfonyTestApp\Kernel('test', true);

//dd($app->getProjectDir());
$app->boot();

dd(
//  $app->getContainer()->get('ViergeNoirePHPUnitListener\Connection\SymfonyInjectedConnection')
//  $app->getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)
  $app->getContainer()->get('doctrine.orm.entity_manager')
);

//$d = $app->getContainer()
//    ->get('doctrine.orm.entity_manager');
//$d = $app->getContainer()->get('test');
////$d = $app->getBundle('doctrine');
//dd($d);

//$test = new \ViergeNoirePHPUnitListener\Connection\SymfonyInjectedConnection();

//$config = Setup::createAnnotationMetadataConfiguration($paths, true);
//$entityManager = EntityManager::create($baseConfig, $config);


//$baseConfig = [
//    "driver" => "mysql",
//    "host" =>"Mysql",
//    "database" => "test_phpunit_listener",
//    "username" => "root",
//    "password" => "root"
//];


//\Illuminate\Support\Facades\Artisan::call('migrate', array('--path' => 'tests/TestApp/config/Migrations', '--force' => true));
