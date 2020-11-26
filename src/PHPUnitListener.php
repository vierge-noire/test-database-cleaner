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
namespace ViergeNoirePHPUnitListener;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use ViergeNoirePHPUnitListener\ConnectionManager\ConnectionManagerInterface;

/**
 * Class FixtureInjector
 * @package ViergeNoirePHPUnitListener
 */
class PHPUnitListener implements TestListener
{
    /**
     * @var DatabaseCleaner
     */
    public $databaseCleaner;

    public function __construct(ConnectionManagerInterface $connectionAdapter)
    {
        $this->databaseCleaner = new DatabaseCleaner($connectionAdapter);
    }

    /**
     * Nothing to do there. The tables should be created
     * in tests/bootstrap.php, either by migration or by running
     * the relevant Sql commands on the test DBs
     * See the Migrator tool provided here:
     * https://github.com/vierge-noire/cakephp-test-migrator
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite): void
    {}

    /**
     * Cleanup before test starts
     * Truncates the tables that were used by the previous test before starting a new one
     * The truncation may be by-passed by setting in the test
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @return void
     */
    public function startTest(Test $test): void
    {
        // Truncation can be skipped if no DB interaction are expected
        if (!$this->skipTablesTruncation($test)) {
            $this->databaseCleaner->truncateDirtyTables();
        }
    }

    /**
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param float                   $time current time
     * @return void
     */
    public function endTest(Test $test, $time): void
    {}

    /**
     * The tables are not truncated at the end of the suite.
     * This way one can observe the content of the test DB
     * after a suite has been run.
     *
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite): void
    {}

    /**
     * If a test uses the SkipTablesTruncation trait, table truncation
     * does not occur between tests
     * @param Test $test
     * @return bool
     */
    public function skipTablesTruncation(Test $test): bool
    {
        return isset($test->skipTablesTruncation) ? $test->skipTablesTruncation : false;
    }

    /**
     * @inheritDoc
     */
    public function addError(Test $test, \Exception $e, $time): void
    {}

    /**
     * @inheritDoc
     */
    public function addWarning(Test $test, Warning $e, $time): void
    {}

    /**
     * @inheritDoc
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time): void
    {}

    /**
     * @inheritDoc
     */
    public function addIncompleteTest(Test $test, \Exception $e, $time): void
    {}

    /**
     * @inheritDoc
     */
    public function addRiskyTest(Test $test, \Exception $e, $time): void
    {}

    /**
     * @inheritDoc
     */
    public function addSkippedTest(Test $test, \Exception $e, $time): void
    {}
}
