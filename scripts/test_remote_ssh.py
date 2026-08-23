import paramiko
import time
import socket

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'

print("1. Testing raw TCP socket to 76.13.79.242:22...")
try:
    sock = socket.create_connection((HOST, 22), timeout=10)
    banner = sock.recv(1024)
    print(f"Raw banner received: {banner}")
    sock.close()
except Exception as e:
    print(f"Raw socket failed: {e}")

print("\n2. Testing Paramiko SSH with banner_timeout=30...")
for attempt in range(1, 4):
    try:
        print(f"Attempt {attempt}...")
        client = paramiko.SSHClient()
        client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        client.connect(HOST, port=22, username=USER, password=PASS, timeout=30, banner_timeout=60, auth_timeout=30)
        print("✓ Connected successfully via Paramiko!")
        stdin, stdout, stderr = client.exec_command("whoami; pwd; git --version; php -v | head -n 1")
        print("Output:\n" + stdout.read().decode('utf-8'))
        client.close()
        break
    except Exception as e:
        print(f"Attempt {attempt} failed: {e}")
        time.sleep(2)
