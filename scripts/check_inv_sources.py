import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Webkul\Inventory\Models\InventorySource;
use Webkul\Core\Models\Channel;
use Illuminate\Support\Facades\DB;

$sources = InventorySource::all();
echo 'ALL_INVENTORY_SOURCES:' . json_encode($sources->toArray()) . PHP_EOL;

$channels = Channel::with('inventory_sources')->get();
foreach ($channels as $c) {
    echo 'CHANNEL_ID:' . $c->id . ' | CODE:' . $c->code . ' | NAME:' . $c->name . PHP_EOL;
    echo '  LINKED_INVENTORY_SOURCES:' . json_encode($c->inventory_sources->toArray()) . PHP_EOL;
}

$channelSourcesTable = DB::table('channel_inventory_sources')->get();
echo 'CHANNEL_INVENTORY_SOURCES_TABLE:' . json_encode($channelSourcesTable->toArray()) . PHP_EOL;
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/check_inv_sources.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php check_inv_sources.php && rm check_inv_sources.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
