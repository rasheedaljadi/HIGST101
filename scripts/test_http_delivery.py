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

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    print(out.strip())
    if err:
        print("ERR:", err.strip())

run_cmd("curl -I -k https://127.0.0.1/delivery -H 'Host: highest-ye.store'")
run_cmd("curl -I -k https://127.0.0.1/admin/login -H 'Host: highest-ye.store'")
run_cmd("curl -I -k https://highest-ye.store/delivery")

client.close()
