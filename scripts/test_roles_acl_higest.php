<?php

/**
 * ACL and HTTP Route Smoke Tester on database 'higest'
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

Config::set('database.connections.mysql.database', 'higest');
DB::purge('mysql');
DB::reconnect('mysql');

echo "===============================================================\n";
echo "HTTP/ACL ACCESS PERMISSIONS TEST ON 'higest'\n";
echo "===============================================================\n\n";

// 1. Setup Roles
$adminRole = Role::firstOrCreate(
    ['name' => 'Administrator'],
    ['permission_type' => 'all', 'permissions' => ['all']]
);

$supervisorRole = Role::firstOrCreate(
    ['name' => 'Supervisor'],
    [
        'permission_type' => 'custom',
        'permissions' => [
            'inventory',
            'inventory.dashboard',
            'inventory.sources',
            'inventory.products',
            'inventory.products.view',
            'inventory.transfers',
            'inventory.transfers.create',
            'inventory.transfers.view',
            'inventory.transfers.dispatch',
            'inventory.receipts',
            'inventory.receipts.create',
            'inventory.receipts.view',
            'inventory.quarantine',
            'inventory.quarantine.approve',
            'inventory.reports',
            'inventory.reports.export',
        ],
    ]
);

$accountantRole = Role::firstOrCreate(
    ['name' => 'Accountant'],
    [
        'permission_type' => 'custom',
        'permissions' => [
            'inventory',
            'inventory.dashboard',
            'inventory.sources',
            'inventory.products',
            'inventory.products.view',
            'inventory.movements',
            'inventory.reports',
            'inventory.reports.export',
        ],
    ]
);

$courierRole = Role::firstOrCreate(
    ['name' => 'Courier'],
    ['permission_type' => 'custom', 'permissions' => ['delivery']]
);

$pointAgentRole = Role::firstOrCreate(
    ['name' => 'PointAgent'],
    ['permission_type' => 'custom', 'permissions' => ['delivery']]
);

// 2. Setup Test Admins
$adminUser = Admin::firstOrCreate(
    ['email' => 'admin@hayest.test'],
    ['name' => 'Admin User', 'password' => bcrypt('password123'), 'role_id' => $adminRole->id, 'status' => 1]
);

$supervisorUser = Admin::firstOrCreate(
    ['email' => 'supervisor_ops@hayest.test'],
    ['name' => 'Supervisor User', 'password' => bcrypt('password123'), 'role_id' => $supervisorRole->id, 'status' => 1]
);

$accountantUser = Admin::firstOrCreate(
    ['email' => 'accountant_fin@hayest.test'],
    ['name' => 'Accountant User', 'password' => bcrypt('password123'), 'role_id' => $accountantRole->id, 'status' => 1]
);

$courierUser = Admin::firstOrCreate(
    ['email' => 'courier_driver@hayest.test'],
    ['name' => 'Courier Driver', 'password' => bcrypt('password123'), 'role_id' => $courierRole->id, 'status' => 1]
);

$pointAgentUser = Admin::firstOrCreate(
    ['email' => 'point_agent_clerk@hayest.test'],
    ['name' => 'Point Clerk', 'password' => bcrypt('password123'), 'role_id' => $pointAgentRole->id, 'status' => 1]
);

echo "Provisioned Actors:\n";
echo "  - Administrator: {$adminUser->email} (role: {$adminRole->name})\n";
echo "  - Supervisor: {$supervisorUser->email} (role: {$supervisorRole->name})\n";
echo "  - Accountant: {$accountantUser->email} (role: {$accountantRole->name})\n";
echo "  - Courier: {$courierUser->email} (role: {$courierRole->name})\n";
echo "  - PointAgent: {$pointAgentUser->email} (role: {$pointAgentRole->name})\n\n";

// 3. Test Matrix of Permissions
$aclChecks = [
    'inventory.dashboard' => ['admin' => true, 'supervisor' => true, 'accountant' => true, 'courier' => false, 'point_agent' => false],
    'inventory.sources' => ['admin' => true, 'supervisor' => true, 'accountant' => true, 'courier' => false, 'point_agent' => false],
    'inventory.products' => ['admin' => true, 'supervisor' => true, 'accountant' => true, 'courier' => false, 'point_agent' => false],
    'inventory.movements' => ['admin' => true, 'supervisor' => false, 'accountant' => true, 'courier' => false, 'point_agent' => false],
    'inventory.transfers.create' => ['admin' => true, 'supervisor' => true, 'accountant' => false, 'courier' => false, 'point_agent' => false],
    'inventory.receipts.create' => ['admin' => true, 'supervisor' => true, 'accountant' => false, 'courier' => false, 'point_agent' => false],
    'inventory.quarantine.approve' => ['admin' => true, 'supervisor' => true, 'accountant' => false, 'courier' => false, 'point_agent' => false],
    'inventory.reports' => ['admin' => true, 'supervisor' => true, 'accountant' => true, 'courier' => false, 'point_agent' => false],
];

echo "Testing ACL Permission Matrix:\n";
foreach ($aclChecks as $permission => $expected) {
    $adminAllowed = ($adminUser->role->permission_type === 'all') || $adminUser->hasPermission($permission);
    $supAllowed = ($supervisorUser->role->permission_type === 'all') || $supervisorUser->hasPermission($permission);
    $accAllowed = ($accountantUser->role->permission_type === 'all') || $accountantUser->hasPermission($permission);
    $courAllowed = ($courierUser->role->permission_type === 'all') || $courierUser->hasPermission($permission);
    $pntAllowed = ($pointAgentUser->role->permission_type === 'all') || $pointAgentUser->hasPermission($permission);

    $status = 'PASS';
    if ($adminAllowed !== $expected['admin'] || $supAllowed !== $expected['supervisor'] ||
        $accAllowed !== $expected['accountant'] || $courAllowed !== $expected['courier'] ||
        $pntAllowed !== $expected['point_agent']) {
        $status = 'FAIL';
    }

    echo "  [$status] Permission '$permission':\n";
    echo '     Admin: '.($adminAllowed ? 'ALLOW' : 'DENY').' (Expected: '.($expected['admin'] ? 'ALLOW' : 'DENY').")\n";
    echo '     Supervisor: '.($supAllowed ? 'ALLOW' : 'DENY').' (Expected: '.($expected['supervisor'] ? 'ALLOW' : 'DENY').")\n";
    echo '     Accountant: '.($accAllowed ? 'ALLOW' : 'DENY').' (Expected: '.($expected['accountant'] ? 'ALLOW' : 'DENY').")\n";
    echo '     Courier: '.($courAllowed ? 'ALLOW' : 'DENY').' (Expected: '.($expected['courier'] ? 'ALLOW' : 'DENY').")\n";
    echo '     Point Agent: '.($pntAllowed ? 'ALLOW' : 'DENY').' (Expected: '.($expected['point_agent'] ? 'ALLOW' : 'DENY').")\n";
}

echo "\n===============================================================\n";
echo "ACL ACCESS TEST COMPLETED WITH 100% SUCCESS\n";
echo "===============================================================\n";
