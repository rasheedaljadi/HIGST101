<?php

namespace Webkul\Notification\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Models\SyncRun;

class ScheduledSyncNotificationService
{
    /**
     * Create a notification summarizing a completed SyncRun.
     */
    public function notifySyncCompleted(string $runId, array $statistics = [], array $healthSnapshot = []): void
    {
        try {
            $syncRun = class_exists(SyncRun::class) ? SyncRun::find($runId) : null;
            $rawProvider = $syncRun ? $syncRun->provider : 'aliexpress';
            $provider = strtolower($rawProvider) === 'aliexpress' ? 'AliExpress' : ucfirst($rawProvider);
            $status = $syncRun ? $syncRun->status : 'COMPLETED';

            $scanned = $statistics['scanned'] ?? ($statistics['total_items'] ?? 0);
            $published = $statistics['published'] ?? ($statistics['synced_items'] ?? ($statistics['changed'] ?? 0));
            $errors = $statistics['errors_count'] ?? ($statistics['failed_items'] ?? 0);
            $duration = $healthSnapshot['duration_sec'] ?? '0';

            $hasErrors = ($errors > 0 || $status === 'COMPLETED_WITH_ERRORS');

            $title = $hasErrors
                ? "اكتملت المزامنة المجدولة مع تنبيهات ({$provider})"
                : "اكتملت المزامنة المجدولة بنجاح ({$provider})";

            $message = "ملخص المزامنة المجدولة ({$provider}):\n"
                ."• المنتجات المفحوصة: {$scanned}\n"
                ."• التحديثات المنشورة: {$published}\n"
                ."• الأخطاء: {$errors}\n"
                ."• المدة: {$duration} ثانية\n"
                .'• الحالة: '.($hasErrors ? 'مكتملة مع ملاحظات' : 'مكتملة بنجاح');

            $this->createAdminNotification(
                type: 'scheduled_sync',
                title: $title,
                message: $message,
                actionUrl: '/admin/dropshipping/sync',
                eventKey: "sync_run:{$runId}:completed",
                entityType: class_exists(SyncRun::class) ? SyncRun::class : null,
                entityId: null
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create sync completed notification: '.$e->getMessage());
        }
    }

    /**
     * Create a notification summarizing a failed SyncRun.
     */
    public function notifySyncFailed(string $runId, string $errorMessage): void
    {
        try {
            $syncRun = class_exists(SyncRun::class) ? SyncRun::find($runId) : null;
            $rawProvider = $syncRun ? $syncRun->provider : 'aliexpress';
            $provider = strtolower($rawProvider) === 'aliexpress' ? 'AliExpress' : ucfirst($rawProvider);

            $title = "فشل في عملية المزامنة المجدولة ({$provider})";
            $message = "فشلت عملية المزامنة المجدولة ({$provider}):\n"
                ."• سبب الفشل: {$errorMessage}\n"
                ."• معرف الجلسة: {$runId}\n"
                .'يرجى مراجعة صفحة إدارة المزامنة للتفاصيل.';

            $this->createAdminNotification(
                type: 'scheduled_sync',
                title: $title,
                message: $message,
                actionUrl: '/admin/dropshipping/sync',
                eventKey: "sync_run:{$runId}:failed",
                entityType: class_exists(SyncRun::class) ? SyncRun::class : null,
                entityId: null
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create sync failed notification: '.$e->getMessage());
        }
    }

    /**
     * Create a general sync summary notification (e.g. for orders poll or custom batch syncs).
     */
    public function notifyGeneralSync(string $syncTitle, string $summaryMessage, ?string $eventKey = null): void
    {
        try {
            $this->createAdminNotification(
                type: 'scheduled_sync',
                title: $syncTitle,
                message: $summaryMessage,
                actionUrl: '/admin/dropshipping/sync',
                eventKey: $eventKey ?? ('sync_general:'.uniqid())
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create general sync notification: '.$e->getMessage());
        }
    }

    /**
     * Store admin notification in database.
     */
    protected function createAdminNotification(
        string $type,
        string $title,
        string $message,
        string $actionUrl,
        string $eventKey,
        ?string $entityType = null,
        mixed $entityId = null
    ): void {
        DB::table('notifications')->insert([
            'type' => $type,
            'customer_id' => null,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'event_key' => $eventKey,
            'entity_type' => $entityType,
            'entity_id' => is_numeric($entityId) ? $entityId : null,
            'order_id' => null,
            'read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
