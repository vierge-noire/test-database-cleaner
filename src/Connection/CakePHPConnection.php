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
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;

class CakePHPConnection extends AbstractConnection
{
    public function execute(string $stmt): StatementInterface
    {
        return ConnectionManager::get($this->getConnectionName())->execute($stmt);
    }

    /**
     * @inheritDoc
     */
    public function filterMigrationTables(array $tables): array
    {
        foreach ($tables as $i => $table) {
            if (strpos($table, 'phinxlog') !== false) {
                unset($tables[$i]);
            }
        }
        return $tables;
    }

    /**
     * @inheritDoc
     */
    public function fetchList(string $stmt, string $field): array
    {
        try {
            $data = $this->execute($stmt)->fetchAll();
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