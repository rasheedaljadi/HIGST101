import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    clean_crontab = """# Crontab for highest-ye
* * * * * cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan schedule:run >> /dev/null 2>&1
* * * * * systemctl --user is-active highest-queue-aliexpress-webhooks.service > /dev/null 2>&1 || systemctl --user start highest-queue-aliexpress-webhooks.service > /dev/null 2>&1
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/crontab.txt', 'w') as f:
        f.write(clean_crontab)
    sftp.close()
    
    run_remote_cmd(client, "crontab /tmp/crontab.txt && rm -f /tmp/crontab.txt")
    code, out, err = run_remote_cmd(client, "crontab -l")
    print("New crontab:\n", out)
    client.close()

if __name__ == '__main__':
    main()
