import paramiko
import time
import socket
import sys

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'

def get_ssh_client(max_retries=5, retry_delay=3):
    for attempt in range(1, max_retries + 1):
        try:
            print(f"[SSH] Connecting to {HOST}:22 as {USER} (attempt {attempt}/{max_retries})...")
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            # Use direct socket with TCP keepalive
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(20)
            sock.connect((HOST, 22))
            
            client.connect(
                HOST,
                port=22,
                username=USER,
                password=PASS,
                sock=sock,
                timeout=30,
                banner_timeout=60,
                auth_timeout=30,
                look_for_keys=False,
                allow_agent=False
            )
            print("[SSH] Connected successfully!")
            return client
        except Exception as e:
            print(f"[SSH] Connection attempt {attempt} failed: {e}")
            if attempt < max_retries:
                time.sleep(retry_delay)
            else:
                raise e

def run_remote_cmd(client, cmd, hide_sensitive=True):
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    return code, out, err

if __name__ == '__main__':
    client = get_ssh_client()
    code, out, err = run_remote_cmd(client, "whoami; hostname; pwd; git --version; php -v | head -n 1")
    print("\n--- Remote Command Output ---")
    print(out)
    client.close()
