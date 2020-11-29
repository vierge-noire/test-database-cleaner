<?php


define('ROOT', dirname(__DIR__));
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

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
$framework = getenv('FRAMEWORK');
echo "Tests on $framework using driver $driver \n";