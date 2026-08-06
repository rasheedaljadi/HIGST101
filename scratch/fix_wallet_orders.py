import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
code = '$orders = \\Webkul\\Sales\\Models\\Order::all(); foreach ($orders as $o) { if (($o->payment->method ?? "") === "wallet" && $o->invoices->count() == 0 && $o->canInvoice()) { $invRepo = app(\\Webkul\\Sales\\Repositories\\InvoiceRepository::class); $data = ["order_id" => $o->id]; foreach ($o->items as $item) { $data["invoice"]["items"][$item->id] = $item->qty_to_invoice; } $inv = $invRepo->create($data, "paid", "processing"); $txnRepo = app(\\Webkul\\Sales\\Repositories\\OrderTransactionRepository::class); $txnRepo->create(["transaction_id" => "WLT-" . strtoupper(bin2hex(random_bytes(6))), "status" => "paid", "type" => "wallet", "payment_method" => "wallet", "order_id" => $o->id, "invoice_id" => $inv->id, "amount" => $inv->grand_total]); echo "Created invoice #" . $inv->id . " and transaction for Wallet Order #" . $o->increment_id . PHP_EOL; } }'

artisan_cmd = f"cd {project_dir} && php artisan tinker --execute='{code}'"

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)
client.close()
