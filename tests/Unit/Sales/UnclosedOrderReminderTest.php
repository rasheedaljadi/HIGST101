<?php

namespace Tests\Unit\Sales;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\NotificationController;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Services\UnclosedOrderReminderService;

class UnclosedOrderReminderTest extends TestCase
{
    use DatabaseTransactions;

    protected UnclosedOrderReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UnclosedOrderReminderService::class);
    }

    /**
     * Helper to create a minimal Order record for testing.
     */
    protected function createTestOrder(string $status, Carbon $createdAt): Order
    {
        $order = new Order;
        $order->increment_id = 'TEST-'.uniqid();
        $order->status = $status;
        $order->channel_name = 'Default';
        $order->customer_email = 'test@example.com';
        $order->customer_first_name = 'Test';
        $order->customer_last_name = 'Customer';
        $order->is_guest = 1;
        $order->total_item_count = 1;
        $order->total_qty_ordered = 1;
        $order->base_currency_code = 'SAR';
        $order->channel_currency_code = 'SAR';
        $order->order_currency_code = 'SAR';
        $order->grand_total = 100;
        $order->base_grand_total = 100;
        $order->sub_total = 100;
        $order->base_sub_total = 100;
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        return $order;
    }

    public function test_it_does_not_remind_for_orders_under_interval(): void
    {
        $order = $this->createTestOrder(Order::STATUS_PENDING, Carbon::now()->subDays(3));

        $result = $this->service->evaluateOrderReminder($order, 5, false);

        $this->assertFalse($result['notified']);
        $this->assertEquals(0, $result['milestone_day']);

        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('order_id', $order->id)
            ->first();

        $this->assertNull($notif);
    }

    public function test_it_creates_reminder_for_unclosed_order_at_5_days(): void
    {
        $order = $this->createTestOrder(Order::STATUS_PENDING, Carbon::now()->subDays(6));

        $result = $this->service->evaluateOrderReminder($order, 5, false);

        $this->assertTrue($result['notified']);
        $this->assertEquals(5, $result['milestone_day']);

        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'order_reminder')
            ->where('order_id', $order->id)
            ->where('event_key', "order_reminder_{$order->id}_5")
            ->first();

        $this->assertNotNull($notif, 'Notification should be created in DB');
        $this->assertStringContainsString('تذكير', $notif->title);
        $this->assertStringContainsString('5', $notif->title);
        $this->assertStringContainsString((string) $order->id, $notif->title);
        $this->assertEquals(0, $notif->read);
    }

    public function test_it_prevents_duplicate_reminders_for_same_milestone(): void
    {
        $order = $this->createTestOrder(Order::STATUS_PENDING, Carbon::now()->subDays(7));

        // First run -> creates notification
        $firstRun = $this->service->evaluateOrderReminder($order, 5, false);
        $this->assertTrue($firstRun['notified']);

        // Second run -> skipped due to duplicate check
        $secondRun = $this->service->evaluateOrderReminder($order, 5, false);
        $this->assertFalse($secondRun['notified']);
        $this->assertStringContainsString('Already notified', $secondRun['reason']);

        // Assert only 1 notification exists in DB
        $count = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('event_key', "order_reminder_{$order->id}_5")
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_it_creates_second_milestone_at_10_days(): void
    {
        $order = $this->createTestOrder(Order::STATUS_PROCESSING, Carbon::now()->subDays(12));

        // Pre-create milestone 5 notification
        DB::table('notifications')->insert([
            'type' => 'order_reminder',
            'order_id' => $order->id,
            'title' => 'تذكير: مضى 5 أيام',
            'message' => 'تفاصيل',
            'event_key' => "order_reminder_{$order->id}_5",
            'read' => 1,
            'created_at' => Carbon::now()->subDays(7),
            'updated_at' => Carbon::now()->subDays(7),
        ]);

        $result = $this->service->evaluateOrderReminder($order, 5, false);

        $this->assertTrue($result['notified']);
        $this->assertEquals(10, $result['milestone_day']);

        $notif10 = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('event_key', "order_reminder_{$order->id}_10")
            ->first();

        $this->assertNotNull($notif10);
        $this->assertStringContainsString('10', $notif10->title);
    }

    public function test_it_skips_terminal_statuses(): void
    {
        $terminalStatuses = [
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELED,
            Order::STATUS_CLOSED,
        ];

        foreach ($terminalStatuses as $status) {
            $order = $this->createTestOrder($status, Carbon::now()->subDays(15));
            $result = $this->service->evaluateOrderReminder($order, 5, false);

            $this->assertFalse($result['notified'], "Should not notify for status: {$status}");
            $this->assertStringContainsString('terminal state', $result['reason']);
        }
    }

    public function test_command_execution(): void
    {
        $this->artisan('orders:check-unclosed-reminders', ['--dry-run' => true])
            ->assertSuccessful();
    }

    public function test_notification_controller_formatting_and_viewing(): void
    {
        $order = $this->createTestOrder(Order::STATUS_PENDING, Carbon::now()->subDays(6));
        $this->service->evaluateOrderReminder($order, 5, false);

        $notif = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'order_reminder')
            ->where('order_id', $order->id)
            ->first();

        $controller = app(NotificationController::class);
        $data = $controller->getNotifications();

        $this->assertNotEmpty($data['search_results']);

        $response = $controller->viewedNotifications($notif->id);
        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString((string) $order->id, $response->getTargetUrl());

        $updatedNotif = DB::table('notifications')->where('id', $notif->id)->first();
        $this->assertEquals(1, $updatedNotif->read);
    }
}
