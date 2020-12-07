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


use Doctrine\ORM\EntityManager;

class SymfonyConnection extends AbstractConnection
{
    public function __construct($em)
    {

    }

    public function getEM(EntityManager $em)
    {
        dd($em);
    }

    public function execute(string $stmt): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function filterMigrationTables(array $tables): array
    {
        foreach ($tables as $i => $table) {
            // TODO: Use 'migration_versions'
            if ($table === 'phinxlog') {
                unset($tables[$i]);
                return $tables;
            }
        }
        return $tables;
    }

    /**
     * @inheritDoc
     */
    public function fetchList(string $stmt, string $field): array
    {
        $this->getEM();

        try {
            $data = Manager::connection($this->getConnectionName())->select($stmt);
        } catch (\Exception $e) {
            $name = $this->getConnectionName();
            var_dump($e->getMessage());
            throw new \Exception("Error in the connection '$name'.");
        }

        foreach ($data as $i => $val) {
            $data[$i] = $val->{$field};
        }

        return $data;
    }
}