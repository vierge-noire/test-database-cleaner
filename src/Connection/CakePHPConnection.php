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


use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;

class CakePHPConnection extends AbstractConnection
{
    public function execute(string $stm): StatementInterface
    {
        return ConnectionManager::get($this->getConnectionName())->execute($stm);
    }

    /**
     * @param string $stm
     * @return array|false
     */
    public function fetch(string $stm)
    {
        return $this->execute($stm)->fetchAll();
    }
}