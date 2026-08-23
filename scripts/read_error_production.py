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
client.connect(HOST, username=USER, password=PASS, timeout=15)

stdin, stdout, stderr = client.exec_command(f"grep -n 'local.ERROR' {APP_DIR}/storage/logs/laravel.log | tail -n 5")
lines = stdout.read().decode('utf-8', errors='replace')
print("Last 5 errors:")
print(lines)

stdin, stdout, stderr = client.exec_command(f"tail -n 250 {APP_DIR}/storage/logs/laravel.log")
full = stdout.read().decode('utf-8', errors='replace')
for entry in full.split('[2026-'):
    if 'local.ERROR' in entry:
        print("------------------- ERROR -------------------")
        print("[2026-" + entry[:500])

client.close()
