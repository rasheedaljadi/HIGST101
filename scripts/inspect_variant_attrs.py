import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$variant = DB::table('products')->where('id', 8711)->first();
echo "Variant:\n";
print_r($variant);

$attrs = DB::table('product_attribute_values')->where('product_id', 8711)->get();
echo "Variant Attributes:\n";
foreach ($attrs as $a) {
    echo "Attribute ID: {$a->attribute_id}, Text: {$a->text_value}, JSON: {$a->json_value}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_variant_attrs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_variant_attrs.php && rm inspect_variant_attrs.php")
print(f"OUTPUT:\n{out}")

client.close()
