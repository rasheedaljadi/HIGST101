<?php

use Webkul\Customer\Models\Customer;
use Webkul\Notification\Models\Notification;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Notification\Services\CustomerNotificationService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('blocks unauthenticated guest from accessing customer notification routes', function () {
    getJson(route('shop.customers.account.notifications.get'))
        ->assertUnauthorized();

    postJson(route('shop.customers.account.notifications.mark_all_as_read'))
        ->assertUnauthorized();
});

it('prevents Customer A from viewing or modifying Customer B notification', function () {
    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();

    $service = app(CustomerNotificationService::class);
    $notificationB = $service->createCustomerNotification(
        customerId: $customerB->id,
        type: 'order',
        title: 'إشعار خاص بالعميل B',
        message: 'محتوى العميل B',
        actionUrl: '/customer/account/orders/view/99',
        eventKey: 'order:99:created'
    );

    // Customer A attempting to mark Customer B's notification as read
    actingAs($customerA, 'customer');

    postJson(route('shop.customers.account.notifications.mark_as_read', ['id' => $notificationB->id]))
        ->assertNotFound();

    // Verify Customer B's notification remains unread (read = 0)
    expect($notificationB->fresh()->read)->toBe(0);
});

it('only marks notifications of authenticated customer as read on markAllAsRead', function () {
    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();

    $service = app(CustomerNotificationService::class);
    $notifA = $service->createCustomerNotification(
        customerId: $customerA->id,
        type: 'order',
        title: 'إشعار A',
        message: 'محتوى A',
        actionUrl: '/customer/account/orders',
        eventKey: 'order:101:created'
    );

    $notifB = $service->createCustomerNotification(
        customerId: $customerB->id,
        type: 'order',
        title: 'إشعار B',
        message: 'محتوى B',
        actionUrl: '/customer/account/orders',
        eventKey: 'order:102:created'
    );

    actingAs($customerA, 'customer');

    postJson(route('shop.customers.account.notifications.mark_all_as_read'))
        ->assertOk()
        ->assertJson(['success' => true, 'total_unread' => 0]);

    expect($notifA->fresh()->read)->toBe(1);
    expect($notifB->fresh()->read)->toBe(0);
});

it('ensures admin notification queries filter out customer notifications', function () {
    $customer = Customer::factory()->create();
    $service = app(CustomerNotificationService::class);

    // Customer Notification
    $customerNotif = $service->createCustomerNotification(
        customerId: $customer->id,
        type: 'order',
        title: 'إشعار عميل',
        message: 'رسالة عميل',
        actionUrl: '/customer/account/orders',
        eventKey: 'order:201:created'
    );

    // Admin Notification
    $adminNotif = Notification::create([
        'customer_id' => null,
        'type' => 'order',
        'read' => 0,
        'order_id' => null,
        'event_key' => null,
    ]);

    $repo = app(NotificationRepository::class);

    $adminResults = $repo->getAll();

    $adminNotificationIds = collect($adminResults['notifications']->items())->pluck('id')->toArray();

    expect($adminNotificationIds)->toContain($adminNotif->id);
    expect($adminNotificationIds)->not()->toContain($customerNotif->id);

    expect($repo->getUnreadCountForCustomer($customer->id))->toBe(1);
});

it('suppresses duplicate customer notifications with identical event_key', function () {
    $customer = Customer::factory()->create();
    $service = app(CustomerNotificationService::class);

    $first = $service->createCustomerNotification(
        customerId: $customer->id,
        type: 'order_status',
        title: 'تحديث حالة',
        message: 'تكتمل المعالجة',
        actionUrl: '/customer/account/orders/view/301',
        eventKey: 'order:301:status:processing'
    );

    $second = $service->createCustomerNotification(
        customerId: $customer->id,
        type: 'order_status',
        title: 'تحديث حالة مكرر',
        message: 'تكتمل المعالجة',
        actionUrl: '/customer/account/orders/view/301',
        eventKey: 'order:301:status:processing'
    );

    expect($first)->not()->toBeNull();
    expect($second)->toBeNull();

    $count = Notification::where('customer_id', $customer->id)->where('event_key', 'order:301:status:processing')->count();
    expect($count)->toBe(1);
});

it('creates distinct customer notifications for different event_keys', function () {
    $customer = Customer::factory()->create();
    $service = app(CustomerNotificationService::class);

    $first = $service->createCustomerNotification(
        customerId: $customer->id,
        type: 'order_status',
        title: 'قيد التجهيز',
        message: 'طلبك قيد التجهيز',
        actionUrl: '/customer/account/orders/view/401',
        eventKey: 'order:401:status:processing'
    );

    $second = $service->createCustomerNotification(
        customerId: $customer->id,
        type: 'order_status',
        title: 'تم المكتمل',
        message: 'طلبك مكتمل',
        actionUrl: '/customer/account/orders/view/401',
        eventKey: 'order:401:status:completed'
    );

    expect($first)->not()->toBeNull();
    expect($second)->not()->toBeNull();
    expect($first->id)->not()->toBe($second->id);
});
