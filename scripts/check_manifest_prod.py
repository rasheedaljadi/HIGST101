import sys
import json
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

stdin, stdout, stderr = client.exec_command(f"cat {APP_DIR}/public/themes/admin/default/build/manifest.json")
content = stdout.read().decode('utf-8', errors='replace')

try:
    manifest = json.loads(content)
    print("Total manifest entries:", len(manifest))
    image_entries = [k for k in manifest.keys() if 'image' in k or 'svg' in k or 'png' in k]
    print(f"Image entries ({len(image_entries)}):")
    for k in image_entries[:20]:
        print(" ", k, "->", manifest[k].get('file'))
except Exception as e:
    print("Error parsing manifest:", e)

client.close()
