import paramiko
import json

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    \$orders = DB::table('orders')->select('id', 'increment_id', 'status', 'grand_total', 'created_at')->get();
    echo 'ORDERS_COUNT:' . \$orders->count() . PHP_EOL;
    foreach (\$orders as \$o) {
        echo 'ORDER: id=' . \$o->id . ' inc=' . \$o->increment_id . ' status=' . \$o->status . ' total=' . \$o->grand_total . PHP_EOL;
    }
    
    if (Schema::hasTable('order_lifecycle_stage_views')) {
        \$views = DB::table('order_lifecycle_stage_views')->get();
        echo 'STAGE_VIEWS_COUNT:' . \$views->count() . PHP_EOL;
        foreach (\$views as \$v) {
            echo 'VIEW: order_id=' . \$v->order_id . ' stage=' . \$v->bottleneck_stage_code . ' exception=' . \$v->is_exception . PHP_EOL;
        }
    }
"
"""
stdin, stdout, stderr = client.exec_command(cmd)
out = stdout.read().decode('utf-8')
err = stderr.read().decode('utf-8')
print("STDOUT:\n", out)
if err:
    print("STDERR:\n", err)
client.close()
