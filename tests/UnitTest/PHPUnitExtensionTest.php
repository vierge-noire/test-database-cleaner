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
namespace TestDatabaseCleaner\Test\UnitTest;

use PHPUnit\Framework\TestCase;
use TestDatabaseCleaner\PHPUnitExtension;
use TestDatabaseCleaner\Test\Fixture\Classes\ClassExtendingTruncateDirtyTablesTrait;
use TestDatabaseCleaner\Test\Fixture\Classes\ClassUsingTraitUsingTruncateDirtyTablesTrait;
use TestDatabaseCleaner\Test\Fixture\Classes\ClassUsingTruncateDirtyTablesTrait;

class PHPUnitExtensionTest extends TestCase
{
    public function testPHPUnitExtension_isTestUsingTruncateDirtyTableTrait_Using_The_Trait(): void
    {
        $extension = new PHPUnitExtension();

        $classesUsingTruncateDirtyTableTrait = [
            ClassUsingTruncateDirtyTablesTrait::class,
            ClassExtendingTruncateDirtyTablesTrait::class,
            ClassUsingTraitUsingTruncateDirtyTablesTrait::class,
        ];

        foreach ($classesUsingTruncateDirtyTableTrait as $class) {
            $this->assertTrue($extension->isTestUsingTruncateDirtyTableTrait($class), "Test failed for $class");
        }
    }

    public function testPHPUnitExtension_isTestUsingTruncateDirtyTableTrait_Class_Does_Not_Use_Trait(): void
    {
        $extension = new PHPUnitExtension();
        $this->assertFalse($extension->isTestUsingTruncateDirtyTableTrait(self::class));
    }

    public function testPHPUnitExtension_isTestUsingTruncateDirtyTableTrait_Class_Does_Not_Exist(): void
    {
        $extension = new PHPUnitExtension();
        $this->expectError();
        $this->expectErrorMessage('class_uses(): Class FooBar does not exist and could not be loaded');
        $extension->isTestUsingTruncateDirtyTableTrait('FooBar');
    }
}
