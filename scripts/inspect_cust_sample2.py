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

stdin, stdout, stderr = client.exec_command(f"sed -n '50,120p' {APP_DIR}/public/cust_sample.html")
print("--- LINES 50-120 OF cust_sample.html ---")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
