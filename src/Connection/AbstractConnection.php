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

namespace ViergeNoirePHPUnitListener\Connection;


use Cake\Database\Exception;

abstract class AbstractConnection
{
    /**
     * @var string $connectionName
     */
    protected $connectionName;

    /**
     * @param string $stmt
     * @return mixed
     */
    abstract public function execute(string $stmt);

    /**
     * @param array $tables
     * @return array
     */
    abstract public function filterMigrationTables(array $tables): array;

    /**
     * @param string $stmt
     * @return mixed
     */
    abstract public function fetch(string $stmt);

    public function __construct(string $connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @param string $stmt
     * @return array
     */
    public function fetchList(string $stmt): array
    {
        try {
            $data = $this->fetch($stmt);
            if ($data === false) {
                throw new \Exception("Failing query: $stmt");
            }
        } catch (\Exception $e) {
            $name = $this->getConnectionName();
            var_dump($e->getMessage());
            throw new Exception("Error in the connection '$name'.");
        }

        foreach ($data as $i => $val) {
            $data[$i] = $val[0] ?? $val['name'];
        }

        return $data;
    }
}