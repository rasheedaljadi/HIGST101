import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
artisan_cmd = f"""cd '{project_dir}' && php artisan tinker --execute="
\$invoices = \\Webkul\\Sales\\Models\\Invoice::all();
foreach (\$invoices as \$inv) {{
    \$exists = \\Webkul\\Sales\\Models\\OrderTransaction::where('invoice_id', \$inv->id)->exists();
    if (!\$exists) {{
        \\Webkul\\Sales\\Models\\OrderTransaction::create([
            'transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(6))),
            'status' => 'paid',
            'type' => \$inv->order->payment->method ?? 'wallet',
            'payment_method' => \$inv->order->payment->method ?? 'wallet',
            'order_id' => \$inv->order_id,
            'invoice_id' => \$inv->id,
            'amount' => \$inv->grand_total,
        ]);
        echo 'Created transaction for Invoice #' . \$inv->id . PHP_EOL;
    }}
}}
"
"""

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)

client.close()
