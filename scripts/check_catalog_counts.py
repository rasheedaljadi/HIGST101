import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    \$mainCats = DB::table('categories')->where('parent_id', 1)->count();
    \$subCats = DB::table('categories')->where('parent_id', '>', 1)->count();
    \$totalCats = DB::table('categories')->where('id', '>', 1)->count();
    \$baseProducts = DB::table('products')->whereNull('parent_id')->count();
    \$variants = DB::table('products')->whereNotNull('parent_id')->count();
    \$totalProducts = DB::table('products')->count();
    
    echo 'MAIN_CATS:' . \$mainCats . PHP_EOL;
    echo 'SUB_CATS:' . \$subCats . PHP_EOL;
    echo 'TOTAL_CATS:' . \$totalCats . PHP_EOL;
    echo 'BASE_PRODUCTS:' . \$baseProducts . PHP_EOL;
    echo 'VARIANTS:' . \$variants . PHP_EOL;
    echo 'TOTAL_PRODUCTS:' . \$totalProducts . PHP_EOL;
"
"""
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
client.close()
