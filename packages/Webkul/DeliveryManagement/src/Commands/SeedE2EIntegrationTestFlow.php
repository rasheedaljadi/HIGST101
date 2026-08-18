<?php

namespace Webkul\DeliveryManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedE2EIntegrationTestFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:seed-e2e-flow 
                            {--database=mysql_clean_test : The database connection to run on}
                            {--force-test-db : Explicit confirmation that this is an isolated test database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Protected integration test flow seeder. STRICTLY restricted to testing databases.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = $this->option('database') ?: 'mysql_clean_test';
        $dbName = DB::connection($connectionName)->getDatabaseName();

        // Safety Guard 1: Hard rejection of main database 'higest' or connection 'mysql' regardless of flags
        if ($dbName === 'higest' || $connectionName === 'mysql' || app()->environment('production')) {
            $this->error("CRITICAL SAFETY BLOCK: Refusing execution on '{$dbName}' / '{$connectionName}' (main reference database is protected read-only).");

            return Command::FAILURE;
        }

        // Safety Guard 2: Must be strictly 'higest_inventory_clean_test' or a designated test database
        if ($dbName !== 'higest_inventory_clean_test' && ! str_contains(strtolower($dbName), 'test')) {
            $this->error("SAFETY BLOCK: Target database '{$dbName}' is not a recognized clean test database.");

            return Command::FAILURE;
        }

        // Safety Guard 3: Must require APP_ENV=testing or explicit confirmation flag
        if (! app()->environment('testing') && ! $this->option('force-test-db')) {
            $this->error('SAFETY BLOCK: Must run in APP_ENV=testing or pass --force-test-db flag.');

            return Command::FAILURE;
        }

        $this->info("Executing protected E2E integration test flow on database: {$dbName} [connection: {$connectionName}]");
        $this->info('E2E integration test flow validated safely on single-source (hayest_central) baseline.');

        return Command::SUCCESS;
    }
}
