import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    files = [
        'packages/Webkul/Procurement/src/Database/Migrations/2026_08_24_000001_add_payment_deadline_at_to_external_platform_orders_table.php',
        'packages/Webkul/Procurement/src/Models/ExternalPlatformOrder.php',
        'packages/Webkul/Procurement/src/Config/procurement.php',
        'packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php',
        'packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php',
        'packages/Webkul/Procurement/src/Services/AliExpressPollingService.php',
        'packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php',
        'packages/Webkul/Procurement/src/Services/ProcurementOrderCancellationService.php',
        'packages/Webkul/Procurement/src/Services/ProcurementBatchService.php',
        'packages/Webkul/Procurement/src/Http/Controllers/Admin/ExternalPlatformOrderController.php',
        'packages/Webkul/Procurement/src/Http/Controllers/Admin/ManualPaymentController.php',
        'packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php',
        'packages/Webkul/Procurement/src/Http/Controllers/Admin/CostVarianceController.php',
        'packages/Webkul/Procurement/src/Routes/admin-routes.php',
        'packages/Webkul/Procurement/src/DataGrids/ExternalPlatformOrderDataGrid.php',
        'packages/Webkul/Procurement/src/Resources/views/admin/platform_orders/index.blade.php',
    ]

    # Add all 21 language files
    lang_dir = os.path.join(base_dir, 'packages', 'Webkul', 'Procurement', 'src', 'Resources', 'lang')
    for loc in os.listdir(lang_dir):
        loc_path = os.path.join('packages', 'Webkul', 'Procurement', 'src', 'Resources', 'lang', loc, 'app.php')
        if os.path.exists(os.path.join(base_dir, loc_path)):
            files.append(loc_path)
            
    print(f"Total files to upload: {len(files)}")
    
    sftp = client.open_sftp()
    for rel_path in files:
        local_abs = os.path.join(base_dir, rel_path)
        remote_abs = f"{remote_base}/{rel_path.replace(os.sep, '/')}"
        remote_dir = os.path.dirname(remote_abs)
        
        # Ensure remote directory exists
        run_remote_cmd(client, f"mkdir -p '{remote_dir}'")
        
        with open(local_abs, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {rel_path}")
        
    sftp.close()
    
    print("\n--- Running Remote Migrations ---")
    code, out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan migrate --force")
    print(out)
    if err:
        print("[ERR]", err)
        
    print("\n--- Clearing Remote Caches ---")
    code, out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan optimize:clear")
    print(out)
    
    print("\n--- Running Immediate Live Polling to Refresh Countdown Timers ---")
    code, out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan procurement:poll-aliexpress")
    print(out)
    if err:
        print("[ERR]", err)
        
    print("\nDeployment complete successfully!")
    client.close()

if __name__ == '__main__':
    main()
