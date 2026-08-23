import paramiko
import sys
import os
import json

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting to {HOST} as {USER}...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    print("SSH Connection Successful!")
    return client

def run_cmd(client, cmd, print_output=True):
    if print_output:
        print(f"\n>>> Running: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if print_output:
        if out:
            print(f"[STDOUT]\n{out.strip()}")
        if err:
            print(f"[STDERR]\n{err.strip()}")
        print(f"[EXIT CODE] {exit_code}")
    return exit_code, out, err

if __name__ == '__main__':
    client = connect()
    
    # 1. Inspect environment and paths
    run_cmd(client, "whoami; pwd; uname -a")
    run_cmd(client, "php -v")
    run_cmd(client, "which php; which composer; which mysql; which mysqldump; which git")
    
    # 2. Locate project directory
    run_cmd(client, "find /home/highest-ye /var/www -maxdepth 3 -name 'artisan' 2>/dev/null")
    
    client.close()
