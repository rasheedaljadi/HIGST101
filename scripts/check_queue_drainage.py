import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    commands = [
        "sleep 6",
        "systemctl --user status highest-queue-aliexpress-webhooks.service | head -n 15",
        "cat /home/highest-ye/htdocs/highest-ye.store/storage/logs/queue-webhooks.log | tail -n 20",
        "cd /home/highest-ye/htdocs/highest-ye.store && php -r \"require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo json_encode(['jobs_count' => Illuminate\Support\Facades\DB::table('jobs')->count(), 'failed_jobs' => Illuminate\Support\Facades\DB::table('failed_jobs')->count(), 'inbox' => Webkul\Procurement\Models\AliExpressWebhookInboxMessage::latest('id')->first()?->only(['id', 'status', 'event_type', 'processed_at'])], JSON_PRETTY_PRINT);\"",
    ]
    
    for cmd in commands:
        code, out, err = run_remote_cmd(client, cmd)
        print(f"[{cmd}]:\n{out}\n")
        
    client.close()

if __name__ == '__main__':
    main()
