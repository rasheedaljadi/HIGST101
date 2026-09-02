import remote_ssh_helper as r

client = r.get_ssh_client()

commands = [
    # 1. Clear all caches
    ("Clear All Optimization Caches", "php8.4 artisan optimize:clear"),
    # 2. Config cache
    ("Build Config Cache", "php8.4 artisan config:cache"),
    # 3. Route cache
    ("Build Route Cache", "php8.4 artisan route:cache"),
    # 4. View cache
    ("Build View Cache", "php8.4 artisan view:cache"),
    # 5. Event cache
    ("Build Event Cache", "php8.4 artisan event:cache"),
    # 6. Check storage permissions
    ("Check Storage & Bootstrap Cache Permissions", "ls -ld storage bootstrap/cache"),
    # 7. Check if site is healthy & responding
    ("Check HTTP 200 Health", "curl -I -s https://highest-ye.store/admin/login | head -n 5"),
]

remote_base = "/home/highest-ye/htdocs/highest-ye.store"

print("=========================================================")
print("RUNNING FULL SYSTEM INTEGRITY & CACHE VALIDATION")
print("=========================================================\n")

for label, cmd in commands:
    print(f"--- [ {label} ] ---")
    code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && {cmd}")
    print(f"Exit Code: {code}")
    if out:
        print(f"Output:\n{out.strip()}")
    if err:
        print(f"Error:\n{err.strip()}")
    print()

client.close()
