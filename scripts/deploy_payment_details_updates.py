import remote_ssh_helper as r

client = r.get_ssh_client()

files_to_sync = [
    ("packages/Webkul/OfflinePayments/src/Listeners/SavePaymentSnapshot.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/OfflinePayments/src/Listeners/SavePaymentSnapshot.php"),
    ("packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php"),
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/index.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Shop/src/Resources/views/checkout/onepage/index.blade.php"),
    ("packages/Webkul/Shop/src/Resources/views/customers/account/orders/pdf.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Shop/src/Resources/views/customers/account/orders/pdf.blade.php"),
    ("packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php"),
    ("packages/Webkul/Admin/src/Resources/views/sales/invoices/view.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Admin/src/Resources/views/sales/invoices/view.blade.php"),
    ("packages/Webkul/Shop/src/Resources/views/checkout/success.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Shop/src/Resources/views/checkout/success.blade.php"),
    ("packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php", "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php"),
]

sftp = client.open_sftp()
for local_path, remote_path in files_to_sync:
    print(f"Uploading {local_path} -> {remote_path}...")
    with open(f"e:/HIGESTO NEW1/higest/higest101/{local_path}", "rb") as f:
        sftp.putfo(f, remote_path)
sftp.close()

print("All files synced! Clearing caches on remote...")
cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan view:clear && php8.4 artisan cache:clear && php8.4 artisan config:clear && php8.4 artisan route:clear"
code, out, err = r.run_remote_cmd(client, cmd)
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
