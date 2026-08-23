import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    echo 'CATEGORIES_SCHEMA: ' . json_encode(Schema::getColumnListing('categories')) . PHP_EOL;
    echo 'PRODUCTS_SCHEMA: ' . json_encode(Schema::getColumnListing('products')) . PHP_EOL;
    if (Schema::hasTable('product_variants')) {
        echo 'PRODUCT_VARIANTS_COUNT: ' . DB::table('product_variants')->count() . PHP_EOL;
    }
    echo 'TOTAL_CATEGORIES: ' . DB::table('categories')->count() . PHP_EOL;
    echo 'ROOT_CATEGORIES (parent_id is null or parent_id=1): ' . DB::table('categories')->where('parent_id', 1)->count() . PHP_EOL;
    echo 'SUB_CATEGORIES (parent_id > 1): ' . DB::table('categories')->where('parent_id', '>', 1)->count() . PHP_EOL;
    echo 'ALL_CAT_PARENTS: ' . json_encode(DB::table('categories')->select('id', 'parent_id', 'position', 'status')->get()->toArray()) . PHP_EOL;
    echo 'PRODUCTS_BY_TYPE: ' . json_encode(DB::table('products')->selectRaw('type, parent_id is not null as has_parent, count(*) as cnt')->groupBy('type', 'has_parent')->get()->toArray()) . PHP_EOL;
    echo 'TOTAL_PRODUCTS: ' . DB::table('products')->count() . PHP_EOL;
"
"""
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
client.close()
