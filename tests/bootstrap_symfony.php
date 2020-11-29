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

putenv('FRAMEWORK=Symfony');

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

$config = Setup::createAnnotationMetadataConfiguration($paths, true);
$entityManager = EntityManager::create($baseConfig, $config);


//$baseConfig = [
//    "driver" => "mysql",
//    "host" =>"Mysql",
//    "database" => "test_phpunit_listener",
//    "username" => "root",
//    "password" => "root"
//];


require_once "tests/bootstrap_cakephp.php";


//\Illuminate\Support\Facades\Artisan::call('migrate', array('--path' => 'tests/TestApp/config/Migrations', '--force' => true));
