import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

commands = [
    f'cd {APP_DIR} && git fetch origin && git reset --hard origin/feat/delivery-admin-ui-rebuild',
    f'cd {APP_DIR} && php artisan view:clear && php artisan route:clear && php artisan config:clear',
]

for cmd in commands:
    print(f"=== RUNNING: {cmd} ===")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    if out:
        print(out)
    if err:
        print("STDERR:", err)

# Test Notification API
test_php = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$controller = app(\\Webkul\\Admin\\Http\\Controllers\\NotificationController::class);

// 1. Header Dropdown simulation
request()->merge(['limit' => 5, 'read' => 0]);
$res1 = $controller->getNotifications();

echo "=== 1. HEADER DROPDOWN NOTIFICATIONS ===\n";
echo "Total Unread: " . $res1['total_unread'] . "\n";
foreach ($res1['search_results']->items() as $n) {
    echo "  [#{$n->id}] Title: '{$n->display_title}'\n";
    echo "       Msg:   '{$n->display_message}'\n";
    echo "       Cat:   '{$n->category}' | Time: '{$n->time_ago}'\n";
    echo "       URL:   '{$n->action_url}'\n\n";
}

// 2. Full Page categories simulation
echo "=== 2. CATEGORY COUNTS IN FULL PAGE ===\n";
echo json_encode($res1['category_counts'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// 3. Orders tab filter
request()->merge(['category' => 'orders', 'limit' => 3]);
$res2 = $controller->getNotifications();
echo "\n=== 3. ORDERS TAB ITEMS ===\n";
foreach ($res2['search_results']->items() as $n) {
    echo "  [#{$n->id}] Title: '{$n->display_title}' | Msg: '{$n->display_message}'\n";
}

// 4. Inventory tab filter
request()->merge(['category' => 'inventory', 'limit' => 3]);
$res3 = $controller->getNotifications();
echo "\n=== 4. INVENTORY TAB ITEMS ===\n";
foreach ($res3['search_results']->items() as $n) {
    echo "  [#{$n->id}] Title: '{$n->display_title}' | Msg: '{$n->display_message}'\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/test_new_notifications.php', 'w') as f:
    f.write(test_php)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php test_new_notifications.php && rm -f test_new_notifications.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
