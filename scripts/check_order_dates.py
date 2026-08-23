import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    foreach (DB::table('orders')->select('id', 'created_at')->get() as \$o) {
        echo \$o->id . ': ' . \$o->created_at . PHP_EOL;
    }
"
"""
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
client.close()
