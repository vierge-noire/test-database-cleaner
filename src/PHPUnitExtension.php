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
namespace TestDataBaseCleaner;

use PHPUnit\Runner\BeforeTestHook;

/**
 * PHPUnit extension to clean up the test databases prior to each test.
 */
class PHPUnitExtension implements BeforeTestHook
{
    /**
     * Cleans up the test databases prior to each tests
     *
     * @param  string $test Test class
     * @return void
     */
    public function executeBeforeTest(string $test): void
    {
        if (!$this->isTestUsingTruncateDirtyTableTrait($test)) {
            return;
        }

        foreach (ConnectionRegistry::getConnections() as $alias => $connectionCleaners) {
            $connectionCleaners->truncateDirtyTables();
        }
    }

    /**
     * @param  string $test Test class name
     * @return bool
     */
    public function isTestUsingTruncateDirtyTableTrait(string $test): bool
    {
        return in_array(
            TruncateDirtyTablesTrait::class,
            $this->classUsesDeep($test)
        );
    }

    /**
     * Inspired from https://www.php.net/manual/en/function.class-uses.php#125933
     *
     * @param  string $class Class used
     * @return string[]
     */
    protected function classUsesDeep(string $class): array
    {
        $traits = class_uses($class);
        if ($traits === false) {
            return [];
        }

        $parent = get_parent_class($class);
        if ($parent) {
            $traits = array_merge($traits, $this->classUsesDeep($parent));
        }

        foreach ($traits as $trait) {
            $traits = array_merge($traits, $this->classUsesDeep($trait));
        }

        return $traits;
    }
}
