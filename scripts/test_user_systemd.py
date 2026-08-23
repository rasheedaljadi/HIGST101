import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    commands = [
        "mkdir -p ~/.config/systemd/user",
        "systemctl --user status 2>&1",
        "loginctl show-user highest-ye 2>&1",
    ]
    
    for cmd in commands:
        code, out, err = run_remote_cmd(client, cmd)
        print(f"CMD: {cmd}\nOUT:\n{out}\nERR:\n{err}\n")
        
    client.close()

if __name__ == '__main__':
    main()
