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

stdin, stdout, stderr = client.exec_command(f"python3 -c \"
with open('{APP_DIR}/storage/logs/laravel.log', 'r', errors='ignore') as f:
    lines = f.readlines()
for line in lines[-100:]:
    print(line, end='')
\"")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
