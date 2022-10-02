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
namespace TestDataBaseCleaner\Sniffer;

class PostgresTableSniffer extends BaseTableSniffer
{
    /**
     * @inheritDoc
     */
    public function truncateDirtyTables(): void
    {
        $this->execute('CALL TruncateDirtyTables();');
        $this->execute('TRUNCATE TABLE ' . self::DIRTY_TABLE_COLLECTOR_NAME . ' RESTART IDENTITY CASCADE;');
    }

    /**
     * @inheritDoc
     */
    public function createTriggerFor(string $trigger, string $table): void
    {
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;

        $this->execute("
                CREATE OR REPLACE FUNCTION mark_table_{$table}_as_dirty() RETURNS TRIGGER LANGUAGE PLPGSQL AS $$
                DECLARE
                    spy_is_inactive {$collectorName}%ROWTYPE;
                BEGIN              
                    INSERT INTO {$collectorName} (table_name) VALUES ('{$table}') ON CONFLICT DO NOTHING;
                    RETURN NEW;
                END;
                $$
            ");

        $this->execute("             
            CREATE TRIGGER {$trigger} AFTER INSERT ON \"{$table}\"                
            FOR EACH ROW
                EXECUTE PROCEDURE mark_table_{$table}_as_dirty();
        ");
    }

    /**
     * @inheritDoc
     */
    public function createTruncateDirtyTablesProcedure(): void
    {
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;

        $this->execute(
            "
            CREATE OR REPLACE PROCEDURE TruncateDirtyTables() AS $$
            DECLARE
                _rec    record;
            BEGIN           
                FOR _rec IN (
                    SELECT  * FROM {$collectorName} dt
                    INNER JOIN information_schema.tables info_schema on dt.table_name = info_schema.table_name
                    WHERE info_schema.table_schema = 'public'
                ) LOOP
                    BEGIN
                        EXECUTE 'TRUNCATE TABLE \"' || _rec.table_name || '\" RESTART IDENTITY CASCADE';
                    END;
                END LOOP;                                
                RETURN;                                
            END $$ LANGUAGE plpgsql;
        "
        );
    }

    /**
     * @inheritDoc
     */
    public function markAllTablesToCleanAsDirty(): void
    {
        $tables = $this->getAllNonIgnoredTables();
        $collectorName = self::DIRTY_TABLE_COLLECTOR_NAME;

        $this->execute(
            "INSERT INTO {$collectorName} VALUES ('" . implode("'), ('", $tables) . "') ON CONFLICT DO NOTHING"
        );
    }

    /**
     * @inheritDoc
     */
    public function getTriggers(): array
    {
        $triggerPrefix = self::TRIGGER_PREFIX;

        return $this->fetchQuery("
            SELECT tgname
            FROM pg_trigger
            WHERE tgname LIKE '{$triggerPrefix}%'
        ");
    }

    /**
     * @inheritDoc
     */
    public function dropTriggers(): void
    {
        $triggers = $this->getTriggers();
        if (empty($triggers)) {
            return;
        }

        foreach ($triggers as $trigger) {
            $table = substr($trigger, strlen(self::TRIGGER_PREFIX));
            $this->execute("DROP TRIGGER {$trigger} ON \"{$table}\";");
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchAllTables(): array
    {
        return $this->fetchQuery(
            "SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY name"
        );
    }
}
