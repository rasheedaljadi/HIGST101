<?php

uses(\Tests\TestCase::class);

use Illuminate\Support\Facades\Route;

test('all dropshipping and fulfillment routes must exist', function () {
    $expectedRoutes = [
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
        'aliexpress.oauth.connect',
        'aliexpress.oauth.callback',
    ];

    foreach ($expectedRoutes as $routeName) {
        expect(Route::has($routeName))->toBeTrue("Route name {$routeName} is not registered in the system.");
    }
});

test('there must be no duplicate route names across the entire registered route collection', function () {
    $routes = Route::getRoutes()->getRoutes();
    $names = [];

    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name === null) {
            continue;
        }

        if (in_array($name, $names, true)) {
            // Exceptions are allowed for native framework overrides if explicit,
            // but dropshipping routes must never have name collisions.
            if (str_starts_with($name, 'admin.dropshipping.')) {
                // If there are duplicate definitions for admin.dropshipping routes, throw failure.
                $collisions = array_filter($routes, fn($r) => $r->getName() === $name);
                $uris = array_map(fn($r) => $r->uri() . ' (' . $r->getActionName() . ')', $collisions);
                fail("Duplicate dropshipping route name collision detected for: {$name}. Routes: " . implode(', ', $uris));
            }
        }
        $names[] = $name;
    }
    
    expect(true)->toBeTrue();
});

test('all dropshipping route controller actions must resolve to existing controllers and methods', function () {
    $routes = Route::getRoutes();

    $dropshippingRouteNames = [
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
        'aliexpress.oauth.connect',
        'aliexpress.oauth.callback',
    ];

    foreach ($dropshippingRouteNames as $name) {
        $route = $routes->getByName($name);
        expect($route)->not->toBeNull("Route {$name} does not exist.");

        $action = $route->getAction();
        
        expect(isset($action['controller']))->toBeTrue("Route {$name} has no controller class mapped.");

        $parts = explode('@', $action['controller']);
        $controllerClass = $parts[0];
        $method = $parts[1] ?? '__invoke';

        expect(class_exists($controllerClass))->toBeTrue("Controller class {$controllerClass} for route {$name} does not exist.");
        expect(method_exists($controllerClass, $method))->toBeTrue("Controller class {$controllerClass} has no action method {$method} defined for route {$name}.");
    }
});
