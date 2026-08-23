import sys
import os
import time
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
REMOTE_APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"[*] Connecting to {HOST} as {USER}...")
    client.connect(HOST, username=USER, password=PASS, timeout=30)
    print("[OK] Connected to production server.")
    return client

def run_cmd(client, cmd, check=True):
    print(f"\n>>> [REMOTE CMD] {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        print(f"[STDOUT]\n{out.strip()}")
    if err.strip():
        print(f"[STDERR]\n{err.strip()}")
    print(f"[EXIT CODE] {exit_code}")
    if check and exit_code != 0:
        raise RuntimeError(f"Command failed with exit code {exit_code}: {cmd}\nStderr: {err}")
    return exit_code, out, err

def sftp_sync_dir(sftp, local_dir, remote_dir):
    print(f"[*] Syncing directory: {local_dir} -> {remote_dir}")
    try:
        sftp.stat(remote_dir)
    except FileNotFoundError:
        parts = remote_dir.strip('/').split('/')
        cur = ""
        for p in parts:
            cur += "/" + p
            try:
                sftp.stat(cur)
            except FileNotFoundError:
                sftp.mkdir(cur)

    for root, dirs, files in os.walk(local_dir):
        rel_path = os.path.relpath(root, local_dir).replace('\\', '/')
        if rel_path == '.':
            target_remote_dir = remote_dir
        else:
            target_remote_dir = f"{remote_dir}/{rel_path}"

        try:
            sftp.stat(target_remote_dir)
        except FileNotFoundError:
            parts = target_remote_dir.strip('/').split('/')
            cur = ""
            for p in parts:
                cur += "/" + p
                try:
                    sftp.stat(cur)
                except FileNotFoundError:
                    sftp.mkdir(cur)

        for f in files:
            local_file = os.path.join(root, f)
            remote_file = f"{target_remote_dir}/{f}"
            sftp.put(local_file, remote_file)

def sftp_sync_file(sftp, local_file, remote_file):
    print(f"[*] Syncing file: {local_file} -> {remote_file}")
    remote_parent = os.path.dirname(remote_file).replace('\\', '/')
    try:
        sftp.stat(remote_parent)
    except FileNotFoundError:
        parts = remote_parent.strip('/').split('/')
        cur = ""
        for p in parts:
            cur += "/" + p
            try:
                sftp.stat(cur)
            except FileNotFoundError:
                sftp.mkdir(cur)
    sftp.put(local_file, remote_file)

if __name__ == '__main__':
    print("=" * 65)
    print("STARTING FULL PRODUCTION DEPLOYMENT PIPELINE ON 76.13.79.242")
    print("=" * 65)

    client = connect()
    sftp = client.open_sftp()

    try:
        # -------------------------------------------------------------
        # STEP 1: PRE-DEPLOYMENT PRODUCTION DATABASE BACKUP & SHA256
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 1: REMOTE DATABASE BACKUP & CHECKSUM")
        print("="*50)
        
        run_cmd(client, "mkdir -p /home/highest-ye/backups")
        backup_ts = time.strftime("%Y%m%d_%H%M%S")
        backup_file = f"/home/highest-ye/backups/production_db_backup_{backup_ts}.sql"
        
        # Take DB backup via mysqldump using credentials from .env
        php_backup_snippet = f"""
$env = file_get_contents('{REMOTE_APP_DIR}/.env');
preg_match('/DB_USERNAME=(.*)/', $env, $u);
preg_match('/DB_PASSWORD=(.*)/', $env, $pw);
preg_match('/DB_DATABASE=(.*)/', $env, $db);
$user = trim($u[1] ?? 'root');
$pass = trim($pw[1] ?? '');
$dbName = trim($db[1] ?? 'highest-db');
$cmd = "mysqldump -h 127.0.0.1 -u $user " . ($pass ? "-p$pass " : "") . "--routines --triggers --events $dbName > {backup_file}";
exec($cmd, $o, $res);
if ($res !== 0) exit(1);
echo "Backup saved: {backup_file}";
"""
        sftp_sync_file(sftp, os.path.join(LOCAL_ROOT, 'scripts', 'check_pending_migrations.php'), f"{REMOTE_APP_DIR}/scripts_check.php")
        
        # Write small backup runner
        with sftp.open(f"{REMOTE_APP_DIR}/run_backup.php", 'w') as f:
            f.write(f"<?php\n{php_backup_snippet}\n")
            
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php run_backup.php && rm run_backup.php")
        run_cmd(client, f"ls -lh {backup_file} && sha256sum {backup_file}")

        # -------------------------------------------------------------
        # STEP 2: CODE SYNC (Packages & Configuration Files)
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 2: UPLOADING PACKAGES & SOURCE FILES VIA SFTP")
        print("="*50)

        # Sync packages
        sftp_sync_dir(sftp, os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Inventory'), f"{REMOTE_APP_DIR}/packages/Webkul/Inventory")
        sftp_sync_dir(sftp, os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Fulfillment'), f"{REMOTE_APP_DIR}/packages/Webkul/Fulfillment")
        sftp_sync_dir(sftp, os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'DeliveryManagement'), f"{REMOTE_APP_DIR}/packages/Webkul/DeliveryManagement")
        
        # Sync test/config files
        sftp_sync_file(sftp, os.path.join(LOCAL_ROOT, 'tests', 'Pest.php'), f"{REMOTE_APP_DIR}/tests/Pest.php")
        sftp_sync_file(sftp, os.path.join(LOCAL_ROOT, 'phpunit.xml'), f"{REMOTE_APP_DIR}/phpunit.xml")
        
        print("[OK] Code synchronization complete.")

        # -------------------------------------------------------------
        # STEP 3: RUN OFFICIAL MIGRATIONS ON PRODUCTION
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 3: RUNNING OFFICIAL MIGRATIONS ON PRODUCTION")
        print("="*50)

        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan migrate --force")

        # -------------------------------------------------------------
        # STEP 4: RUN IDEMPOTENT CANONICAL SEEDERS
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 4: SEEDING CANONICAL INVENTORY SOURCES & GOVERNORATE RULES")
        print("="*50)

        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan db:seed --class=\"Webkul\\Inventory\\Database\\Seeders\\InventorySourcesModelV12Seeder\" --force")
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan db:seed --class=\"Webkul\\DeliveryManagement\\Database\\Seeders\\DeliveryGovernorateRulesSeeder\" --force")

        # -------------------------------------------------------------
        # STEP 5: OPTIMIZE & CLEAR CACHES
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 5: CLEARING & REBUILDING PRODUCTION CACHES")
        print("="*50)

        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan optimize:clear")
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan config:cache")
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan route:cache")
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan view:cache")

        # -------------------------------------------------------------
        # STEP 6: POST-DEPLOYMENT VERIFICATION & HEALTH CHECK
        # -------------------------------------------------------------
        print("\n" + "="*50)
        print("STEP 6: POST-DEPLOYMENT PRODUCTION HEALTH CHECK")
        print("="*50)

        # Health check script
        health_script = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$sources = \\Webkul\\Inventory\\Models\\InventorySource::all();
echo "Total Inventory Sources: " . $sources->count() . PHP_EOL;
foreach ($sources as $s) {
    echo " - ID: {$s->id} | Code: {$s->code} | Name: {$s->name} | Type: {$s->source_type} | Salable: {$s->is_salable} | Delivery: {$s->is_delivery_source}" . PHP_EOL;
}
"""
        with sftp.open(f"{REMOTE_APP_DIR}/health_check.php", 'w') as f:
            f.write(health_script)
            
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php health_check.php && rm health_check.php")

        # Check latest migrations status
        run_cmd(client, f"cd {REMOTE_APP_DIR} && php artisan migrate:status | tail -n 20")

        # Check HTTP response from web server
        run_cmd(client, "curl -I -s http://127.0.0.1/ | head -n 5 || true", check=False)

        print("\n" + "="*65)
        print("PRODUCTION DEPLOYMENT COMPLETED WITH 100% SUCCESS!")
        print("="*65)

    finally:
        sftp.close()
        client.close()
