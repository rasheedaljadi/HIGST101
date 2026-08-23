import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

def connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    return client

def run_cmd(client, cmd):
    print(f"\n>>> Running: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out:
        print(f"[STDOUT]\n{out.strip()}")
    if err:
        print(f"[STDERR]\n{err.strip()}")
    print(f"[EXIT CODE] {exit_code}")
    return exit_code, out, err

if __name__ == '__main__':
    client = connect()
    
    # 1. Check git status and branch on remote
    run_cmd(client, f"cd {APP_DIR} && git status && git branch -a && git log -n 3 --oneline")
    
    # 2. Check DB config from .env (safely grep DB_* without exposing secrets)
    run_cmd(client, f"cd {APP_DIR} && grep -E '^(APP_ENV|DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE)' .env")
    
    # 3. Check migration status
    run_cmd(client, f"cd {APP_DIR} && php artisan migrate:status | tail -n 25")
    
    client.close()
