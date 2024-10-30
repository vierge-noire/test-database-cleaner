<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2020 Vierge Noire Development
 * @link      https://github.com/vierge-noire/test-database-cleaner
 * @since     1.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
const TESTS = ROOT . DS . 'tests' . DS;
const FIXTURE = TESTS . 'Fixture' . DS;

$driver = getenv('DB_DRIVER') ?: 'sqlite';
echo "Using driver $driver \n";

if (!defined('TEST_DNS')) {
    switch ($driver) {
        case 'sqlite':
            $dns = 'sqlite:test.db';
            break;
        case 'mysql':
            $dns = 'mysql:host=db;dbname=db;user=db;password=db';
            break;
        case 'postgres':
            $dns = 'pgsql:host=db;dbname=db;user=db;password=db';
            break;
        default:
            throw new Exception("The driver $driver is not supported.");
    }
    define('TEST_DNS', $dns);
}
