import sys
sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

client = r.get_ssh_client()
cmd = """python3 -c "
with open('/home/highest-ye/htdocs/highest-ye.store/storage/logs/laravel.log', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()
# Find last occurrence of 'local.ERROR'
error_indices = [i for i, line in enumerate(lines) if 'local.ERROR' in line or 'ERROR' in line]
if error_indices:
    last_idx = error_indices[-1]
    print(''.join(lines[last_idx:last_idx+35]))
else:
    print('No ERROR found in log')
" """

code, out, err = r.run_remote_cmd(client, cmd)
print("=== LAST ERROR IN PRODUCTION LOG ===")
print(out)
client.close()
