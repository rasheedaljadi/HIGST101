import sys
import paramiko
import json

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Notification\\Models\\Notification;

$notifications = DB::table('notifications')->orderBy('id', 'desc')->take(15)->get();

echo "=== LATEST 15 NOTIFICATIONS IN DB ===\n";
foreach ($notifications as $n) {
    echo "ID: #{$n->id} | Type: {$n->type} | OrderID: {$n->order_id} | EntityID: {$n->entity_id} | Title: {$n->title} | Read: {$n->read} | Created: {$n->created_at}\n";
}

echo "\n=== NOTIFICATION CONTROLLER RESPONSE SIMULATION ===\n";
$controller = app(\\Webkul\\Admin\\Http\\Controllers\\NotificationController::class);
request()->merge(['limit' => 5, 'read' => 0]);
$res = $controller->getNotifications();
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/inspect_notifications_db.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php inspect_notifications_db.php && rm -f inspect_notifications_db.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
