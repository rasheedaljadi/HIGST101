<?php

uses(\Tests\TestCase::class);

use Illuminate\Support\Facades\Route;

test('every dropshipping admin route must be covered by an ACL permission mapping', function () {
    $routes = Route::getRoutes()->getRoutes();
    $roles = acl()->getRoles(); // Map of [route_name => permission_key] from acl.php

    $dropshippingAdminRouteNames = [
        'admin.dropshipping.fulfillment.index',
        'admin.dropshipping.fulfillment.view',
        'admin.dropshipping.fulfillment.retry',
        'admin.dropshipping.fulfillment.cancel',
        'admin.dropshipping.fulfillment.override',
        'admin.dropshipping.fulfillment.edit',
        'admin.dropshipping.fulfillment.refresh',
        'admin.dropshipping.fulfillment.clear-alert',
        'admin.dropshipping.fulfillment.approve',
        'admin.dropshipping.fulfillment.reject',
        'admin.dropshipping.import.index',
        'admin.dropshipping.import.store',
        'admin.dropshipping.import.stream',
        'admin.dropshipping.keys.index',
        'admin.dropshipping.keys.store',
        'admin.dropshipping.sync.index',
        'admin.dropshipping.sync.run_single',
        'admin.dropshipping.sync.get_all_syncable',
    ];

    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name === null) {
            continue;
        }

        // Only scan admin dropshipping and fulfillment routes that use the admin auth middleware
        if (str_starts_with($name, 'admin.dropshipping.') && in_array('admin', $route->middleware(), true)) {
            expect(isset($roles[$name]))->toBeTrue(
                "Admin route '{$name}' (URI: {$route->uri()}) has no ACL permission mapping in acl.php. Any logged-in admin can access it."
            );
        }
    }
});

test('every dropshipping write operation route (POST, PUT, PATCH, DELETE) must have an explicit capability key', function () {
    $routes = Route::getRoutes()->getRoutes();
    $roles = acl()->getRoles();

    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name === null) {
            continue;
        }

        if (str_starts_with($name, 'admin.dropshipping.') && in_array('admin', $route->middleware(), true)) {
            $methods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if (! empty($methods)) {
                expect(isset($roles[$name]))->toBeTrue(
                    "Write route '{$name}' [{$route->methods()[0]}] must be secured by an explicit ACL capability key."
                );
            }
        }
    }
});
