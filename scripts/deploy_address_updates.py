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
    f'cd {APP_DIR} && php artisan view:clear && php artisan route:clear && php artisan config:clear && php artisan cache:clear',
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

client.close()
