import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

# 1. Sync updated Seeder file
local_seeder = os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Inventory', 'src', 'Database', 'Seeders', 'InventorySourcesModelV12Seeder.php')
remote_seeder = f"{APP_DIR}/packages/Webkul/Inventory/src/Database/Seeders/InventorySourcesModelV12Seeder.php"
sftp.put(local_seeder, remote_seeder)

# 2. Write runner script on remote to execute DB updates
php_script = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$renames = [
    'default' => 'المستودع الافتراضي',
    'hayest_central' => 'مستودع هايست المركزي (صنعاء)',
    'aliexpress_source' => 'مصدر كتالوج علي إكسبرس الافتراضي',
    'hayest_dropship_sa' => 'محطة توريد وتجميع الرياض (السعودية)',
    'hayest_quarantine_sa' => 'مستودع الحجر الصحي بالرياض (السعودية)',
    'hayest_dropship_ye' => 'مركز توزيع دروبشوبنج صنعاء (اليمن)',
    'hayest_internal_ye' => 'مستودع المخزون الجاهز صنعاء (اليمن)',
    'hayest_quarantine_ye' => 'مستودع الحجر الصحي صنعاء (اليمن)',
];

foreach ($renames as $code => $arabicName) {
    DB::table('inventory_sources')
        ->where('code', $code)
        ->update(['name' => $arabicName, 'updated_at' => now()]);
}

echo "Database Warehouse Names Updated Successfully!\n";

$sources = DB::table('inventory_sources')->get();
foreach ($sources as $s) {
    echo "ID: {$s->id} | Code: {$s->code} | Name: {$s->name}\n";
}
"""

with sftp.open(f"{APP_DIR}/run_rename_sources.php", 'w') as f:
    f.write(php_script)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php run_rename_sources.php && rm run_rename_sources.php")
run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

client.close()
print("\n[OK] Warehouse Arabic rename and production cache rebuild completed successfully!")
