<?php


namespace ViergeNoirePHPUnitListener\TableSniffer;


interface TriggerBasedTableSnifferInterface
{
    /**
     * The name of the table collecting dirty tables
     */
    const DIRTY_TABLE_COLLECTOR = 'test_suite_light_dirty_tables';
    const TRIGGER_PREFIX        = 'dirty_table_spy_';

    /**
     * Create triggers on all tables listening to inserts
     * @return void
     */
    public function createTriggers();

    /**
     * List all triggers
     * created by the interface
     * @return array
     */
    public function getTriggers(): array;

    /**
     * Drop all triggers
     * created by the interface
     * @return void
     */
    public function dropTriggers();

    /**
     * Create the table gathering the dirty tables
     * @return void
     */
    public function createDirtyTableCollector();



}