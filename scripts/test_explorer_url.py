import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$url = 'https://api-sg.aliexpress.com/sync?ds_extend_request=%7B%22nat_addr%22%3A+%22RMAD3455%22%7D&param_place_order_request4_open_api_d_t_o=%7B+++%22product_items%22%3A+%5B+++++%7B+++++++%22product_id%22%3A+1005010737996063%2C+++++++%22product_count%22%3A+1%2C+++++++%22sku_attr%22%3A+%2214%3A201447015%23NO+PAD%22%2C+++++++%22logistics_service_name%22%3A+%22CAINIAO_FULFILLMENT_STD%22+++++%7D+++%5D%2C+++%22logistics_address%22%3A+%7B+++++%22country%22%3A+%22SA%22%2C+++++%22contact_person%22%3A+%22Mostafa+Bamashmous%22%2C+++++%22full_name%22%3A+%22Mostafa+Bamashmous%22%2C+++++%22phone_country%22%3A+%22%2B966%22%2C+++++%22mobile_no%22%3A+%220572124578%22%2C+++++%22province%22%3A+%22Riyadh%22%2C+++++%22city%22%3A+%22Riyadh%22%2C+++++%22address%22%3A+%223455+Ahmad+Bin+Rushd%2C+Al+Aziziyah%22%2C+++++%22zip%22%3A+%2214512%22+++%7D+%7D&method=aliexpress.ds.order.create&app_key=536306&sign_method=sha256&session=50000300311cM8qYbrvlt7k17e3fc98EoaOPcD1k1HHvdRvFcght9PVueGwLT6blMOM3&timestamp=1787886952981&sign=DBE137C97B651D76737CCC1B24DF762F9D90CB6380930624A9AA9991570EA7FD';

$response = Http::get($url);
echo "HTTP Status: " . $response->status() . "\n";
echo "Response Body:\n" . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_explorer_url.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_explorer_url.php && rm test_explorer_url.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
