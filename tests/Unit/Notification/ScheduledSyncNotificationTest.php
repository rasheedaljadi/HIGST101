<?php

namespace Tests\Unit\Notification;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\NotificationController;
use Webkul\Fulfillment\Events\SyncCompleted;
use Webkul\Fulfillment\Events\SyncFailed;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Notification\Services\ScheduledSyncNotificationService;

class ScheduledSyncNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected ScheduledSyncNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ScheduledSyncNotificationService::class);
    }

    public function test_it_creates_notification_on_sync_completed_event()
    {
        $runId = (string) Str::uuid();

        // Create a SyncRun record with required JSON fields
        SyncRun::create([
            'id' => $runId,
            'provider' => 'aliexpress',
            'status' => SyncRun::STATUS_COMPLETED,
            'cursor' => ['offset' => 0],
            'metadata' => ['provider' => 'aliexpress'],
            'statistics' => [
                'scanned' => 50,
                'published' => 12,
                'errors_count' => 0,
            ],
            'health_snapshot' => [
                'duration_sec' => 3.5,
            ],
        ]);

        $statistics = [
            'scanned' => 50,
            'published' => 12,
            'errors_count' => 0,
        ];
        $healthSnapshot = [
            'duration_sec' => 3.5,
        ];

        // Trigger SyncCompleted event
        event(new SyncCompleted($runId, $statistics, $healthSnapshot));

        // Assert notification created in database
        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'scheduled_sync')
            ->where('event_key', "sync_run:{$runId}:completed")
            ->first();

        $this->assertNotNull($notif, 'Scheduled sync completed notification should be created.');
        $this->assertStringContainsString('AliExpress', $notif->title);
        $this->assertStringContainsString('50', $notif->message);
        $this->assertStringContainsString('12', $notif->message);
        $this->assertEquals('/admin/dropshipping/sync', $notif->action_url);
    }

    public function test_it_creates_notification_with_warnings_when_errors_occur()
    {
        $runId = (string) Str::uuid();

        SyncRun::create([
            'id' => $runId,
            'provider' => 'aliexpress',
            'status' => SyncRun::STATUS_COMPLETED_WITH_ERRORS,
            'cursor' => ['offset' => 0],
            'metadata' => ['provider' => 'aliexpress'],
            'statistics' => [
                'scanned' => 40,
                'published' => 5,
                'errors_count' => 2,
            ],
            'health_snapshot' => [
                'duration_sec' => 4.2,
            ],
        ]);

        $statistics = [
            'scanned' => 40,
            'published' => 5,
            'errors_count' => 2,
        ];
        $healthSnapshot = [
            'duration_sec' => 4.2,
        ];

        event(new SyncCompleted($runId, $statistics, $healthSnapshot));

        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'scheduled_sync')
            ->where('event_key', "sync_run:{$runId}:completed")
            ->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('تنبيهات', $notif->title);
        $this->assertStringContainsString('الأخطاء: 2', $notif->message);
    }

    public function test_it_creates_notification_on_sync_failed_event()
    {
        $runId = (string) Str::uuid();

        SyncRun::create([
            'id' => $runId,
            'provider' => 'aliexpress',
            'status' => SyncRun::STATUS_FAILED,
            'cursor' => ['offset' => 0],
            'metadata' => ['provider' => 'aliexpress'],
            'statistics' => [],
            'health_snapshot' => [],
        ]);

        event(new SyncFailed($runId, 'OAuth Token Expired'));

        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'scheduled_sync')
            ->where('event_key', "sync_run:{$runId}:failed")
            ->first();

        $this->assertNotNull($notif, 'Scheduled sync failed notification should be created.');
        $this->assertStringContainsString('فشل', $notif->title);
        $this->assertStringContainsString('OAuth Token Expired', $notif->message);
    }

    public function test_notification_controller_formats_and_redirects_scheduled_sync()
    {
        $id = DB::table('notifications')->insertGetId([
            'type' => 'scheduled_sync',
            'customer_id' => null,
            'title' => 'اكتملت المزامنة المجدولة بنجاح (AliExpress)',
            'message' => 'ملخص المزامنة...',
            'action_url' => '/admin/dropshipping/sync',
            'read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(NotificationController::class);
        $res = $controller->getNotifications();

        $this->assertArrayHasKey('search_results', $res);
        $this->assertGreaterThanOrEqual(1, $res['total_unread']);

        // Test redirection to dropshipping sync page
        $redirect = $controller->viewedNotifications($id);
        $this->assertTrue($redirect->isRedirect(route('admin.dropshipping.sync.index')));

        // Assert marked as read
        $notif = DB::table('notifications')->where('id', $id)->first();
        $this->assertEquals(1, $notif->read);
    }
}
