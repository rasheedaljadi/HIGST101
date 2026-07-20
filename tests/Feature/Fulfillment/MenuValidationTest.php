<?php

uses(\Tests\TestCase::class);

use Illuminate\Support\Facades\Route;

test('all dropshipping sidebar menu items must be valid and secure', function () {
    // Retrieve the admin menu from the resolved configuration
    $menuItems = config('menu.admin', []);
    
    expect($menuItems)->not->toBeEmpty('The admin menu configuration is empty.');

    // Filter to dropshipping items
    $dropshippingMenu = array_filter($menuItems, function ($item) {
        return str_starts_with($item['key'], 'dropshipping');
    });

    expect($dropshippingMenu)->not->toBeEmpty('No dropshipping menu entries found.');

    // Retrieve ACL config to check permission mappings
    $aclItems = config('acl', []);
    $aclKeys = array_column($aclItems, 'key');

    foreach ($dropshippingMenu as $item) {
        $key = $item['key'];
        $route = $item['route'] ?? null;

        // 1. Verify route existence if mapped
        if ($route) {
            expect(Route::has($route))->toBeTrue(
                "Menu entry '{$key}' points to a non-existent route name: '{$route}'."
            );

            // 2. Verify controller action existence
            $routeInstance = Route::getRoutes()->getByName($route);
            expect($routeInstance)->not->toBeNull();

            $controllerAction = $routeInstance->getActionName();
            expect($controllerAction)->not->toBe('Closure');

            $parts = explode('@', $controllerAction);
            $controllerClass = $parts[0];
            $method = $parts[1] ?? '__invoke';

            expect(class_exists($controllerClass))->toBeTrue(
                "Controller '{$controllerClass}' for menu '{$key}' does not exist."
            );
            expect(method_exists($controllerClass, $method))->toBeTrue(
                "Action method '{$method}' on controller '{$controllerClass}' for menu '{$key}' does not exist."
            );
        }

        // 3. Verify corresponding ACL mapping key exists
        // Natively, Bagisto uses the menu item's 'key' for the bouncer check
        expect(in_array($key, $aclKeys, true))->toBeTrue(
            "Menu key '{$key}' does not have a corresponding ACL permission key in acl.php configuration."
        );
    }
});
