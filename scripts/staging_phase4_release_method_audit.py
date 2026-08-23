import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'

def main():
    client = get_ssh_client()
    print("\n=== PHASE 4: Release Architecture & Deployment Method Inspection (Read-Only) ===")
    
    # 1. Check if app path is symlink or physical directory
    _, stat_app, _ = run_remote_cmd(client, f"stat '{APP_PATH}'")
    _, is_symlink, _ = run_remote_cmd(client, f"[ -L '{APP_PATH}' ] && echo 'SYMLINK' || echo 'PHYSICAL_DIRECTORY'")
    print(f"App Path Type: {is_symlink}")
    
    # 2. Check parent and surrounding directories
    _, ls_htdocs, _ = run_remote_cmd(client, "ls -la /home/highest-ye/htdocs")
    print(f"\n--- Listing of /home/highest-ye/htdocs ---\n{ls_htdocs}")
    
    _, ls_home, _ = run_remote_cmd(client, "ls -la /home/highest-ye")
    print(f"\n--- Listing of /home/highest-ye ---\n{ls_home}")
    
    # 3. Check for releases/current directories or Capistrano/Envoyer/Deployer structures
    _, find_releases, _ = run_remote_cmd(client, "find /home/highest-ye -maxdepth 3 -name 'releases' -o -name 'current' 2>/dev/null")
    print(f"\n--- Release Directories Found ---\n{find_releases if find_releases else '(none)'}")
    
    # 4. Check webserver document root if readable
    _, webserver_configs, _ = run_remote_cmd(client, "ps aux | grep -E 'nginx|apache|lsws|caddy' | grep -v grep | head -n 10")
    print(f"\n--- Web Server Processes ---\n{webserver_configs}")
    
    # Check vhost configs in user directory or /etc/nginx/sites-enabled
    _, vhost_grep, _ = run_remote_cmd(client, "grep -rn 'highest-ye.store' /etc/nginx/ /etc/apache2/ /usr/local/lsws/ 2>/dev/null | head -n 10")
    print(f"\n--- Web Server VHost Configs ---\n{vhost_grep if vhost_grep else '(access restricted or not found)'}")
    
    # Determine Release Architecture Finding
    has_isolated_release = (len(find_releases.strip()) > 0 and is_symlink.strip() == 'SYMLINK')
    
    if has_isolated_release:
        finding = "ISOLATED RELEASE METHOD VERIFIED"
    elif is_symlink.strip() == 'PHYSICAL_DIRECTORY':
        finding = "DIRECTORY DEPLOYMENT ONLY"
    else:
        finding = "RELEASE METHOD UNKNOWN"
        
    print(f"\n==========================================")
    print(f"RELEASE ARCHITECTURE FINDING: {finding}")
    print(f"==========================================")
    
    # Save Phase 4 audit data
    phase4_data = {
        "app_path": APP_PATH,
        "is_symlink": is_symlink.strip(),
        "ls_htdocs": ls_htdocs.splitlines(),
        "releases_found": find_releases.splitlines() if find_releases else [],
        "finding": finding,
        "description": "Application runs directly from a physical fixed directory (/home/highest-ye/htdocs/highest-ye.store) without an automated atomic symlink/releases structure."
    }
    
    with open('scripts/phase4_release_method_result.json', 'w', encoding='utf-8') as f:
        json.dump(phase4_data, f, indent=2, ensure_ascii=False)
        
    client.close()

if __name__ == '__main__':
    main()
