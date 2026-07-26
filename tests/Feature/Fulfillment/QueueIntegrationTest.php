<?php

namespace Tests\Feature\Fulfillment;

use App\Jobs\AliExpress\SyncProductJob;
use App\Models\AliExpressProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DummyTestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public bool $shouldFail = false) {}

    public function handle(): void
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('Simulated worker queue failure');
        }
    }
}

class QueueIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.connections.database.connection' => config('database.default')]);
        config(['queue.connections.database.table' => 'jobs']);
        config(['queue.connections.database.queue' => 'default']);

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function ($table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    /**
     * Test full async lifecycle: Dispatch -> Queue Storage -> Worker -> Completion -> Jobs table cleanup.
     */
    public function test_async_queue_job_lifecycle_dispatch_store_work_completion(): void
    {
        DB::table('jobs')->truncate();
        DB::table('failed_jobs')->truncate();

        // 1. Dispatch job to database queue connection
        DummyTestQueueJob::dispatch(false)->onConnection('database');

        // 2. Assert job is stored in jobs table
        $this->assertEquals(1, DB::table('jobs')->count());
        $jobRecord = DB::table('jobs')->first();
        $this->assertNotNull($jobRecord);
        $this->assertStringContainsString('DummyTestQueueJob', $jobRecord->payload);

        // 3. Process job via queue worker daemon
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        // 4. Assert job completed and removed from jobs table
        $this->assertEquals(0, DB::table('jobs')->count());

        // 5. Assert no failure recorded in failed_jobs table
        $this->assertEquals(0, DB::table('failed_jobs')->count());
    }

    /**
     * Test job failure handling and dead-letter logging in failed_jobs table.
     */
    public function test_failing_queue_job_lands_in_failed_jobs_table(): void
    {
        DB::table('jobs')->truncate();
        DB::table('failed_jobs')->truncate();

        // 1. Dispatch failing job to database queue
        DummyTestQueueJob::dispatch(true)->onConnection('database');
        $this->assertEquals(1, DB::table('jobs')->count());

        // 2. Process job via queue worker
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        // 3. Assert job was logged to failed_jobs table with specific exception details
        $this->assertEquals(1, DB::table('failed_jobs')->count());
        $failedJob = DB::table('failed_jobs')->first();
        $this->assertNotNull($failedJob);
        $this->assertStringContainsString('RuntimeException', $failedJob->exception);
        $this->assertStringContainsString('Simulated worker queue failure', $failedJob->exception);
    }

    /**
     * System Contract Test:
     * Asserts that SyncProductJob retains $tries = 3 and $backoff = 60 seconds as mandated by system retry SLA.
     * Note: Modifying these values alters background retry behavior and requires Lead Architect review.
     */
    public function test_sync_product_job_retry_and_backoff_configuration(): void
    {
        $import = new AliExpressProductImport(['id' => 99]);
        $job = new SyncProductJob($import);

        // System Contract SLA assertions
        $this->assertEquals(3, $job->tries, 'SyncProductJob must maintain 3 retry attempts as defined in system contract SLA.');
        $this->assertEquals(60, $job->backoff, 'SyncProductJob must maintain 60s backoff delay between retries.');
    }
}
