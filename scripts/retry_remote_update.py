import paramiko
import time

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'

for attempt in range(3):
    try:
        print(f"Connecting to {hostname} (attempt {attempt+1})...")
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(hostname, username=username, password=password, timeout=30)
        
        commands = [
            "git fetch --prune",
            "git merge --ff-only origin/feat/delivery-admin-ui-rebuild",
            "php artisan view:clear",
            "php artisan config:cache",
            "php artisan view:cache",
            "git rev-parse HEAD"
        ]
        
        full_cmd = "cd /home/highest-ye/htdocs/highest-ye.store && " + " && ".join(commands)
        stdin, stdout, stderr = ssh.exec_command(full_cmd, timeout=60)
        out = stdout.read().decode('utf-8')
        err = stderr.read().decode('utf-8')
        
        print("=== REMOTE UPDATE OUTPUT ===")
        print(out)
        if err:
            print("Stderr:", err)
            
        ssh.close()
        break
    except Exception as e:
        print(f"Error on attempt {attempt+1}: {e}")
        time.sleep(3)
