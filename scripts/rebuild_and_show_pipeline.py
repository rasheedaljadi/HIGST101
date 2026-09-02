import remote_ssh_helper as r

client = r.get_ssh_client()

remote_base = "/home/highest-ye/htdocs/highest-ye.store"

rebuild_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Sales\\Services\\Lifecycle\\OrderLifecycleRebuildService;
use Webkul\\Sales\\Services\\Lifecycle\\OrderLifecycleDashboardQueryService;
use Illuminate\\Support\\Facades\\DB;

echo "=========================================================\\n";
echo "1. REBUILDING ORDER LIFECYCLE STAGE VIEWS\\n";
echo "=========================================================\\n";

$rebuilder = app(OrderLifecycleRebuildService::class);
$processed = $rebuilder->rebuild();

echo "Rebuild Completed! Processed Orders: {$processed}\\n";

echo "\\n=========================================================\\n";
echo "2. QUERYING PIPELINE SUMMARY (LIVE DASHBOARD DATA)\\n";
echo "=========================================================\\n";

$queryService = app(OrderLifecycleDashboardQueryService::class);
$pipeline = $queryService->getPipelineSummary();

echo "Stage Counts on Live Dashboard:\\n";
foreach ($pipeline['stages'] as $stg) {
    echo "  [Stage {$stg['rank']}] {$stg['short']} ({$stg['label']}) => {$stg['count']} orders (Value: {$stg['value']})\\n";
}

echo "\\nTotal Active Orders: {$pipeline['total_active_orders']}\\n";
echo "Active Pipeline Count: {$pipeline['active_pipeline_count']}\\n";
echo "Sourcing Decisions Count: {$pipeline['sourcing_decisions_count']}\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{remote_base}/run_lifecycle_rebuild.php", "w") as f:
    f.write(rebuild_php)
sftp.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 run_lifecycle_rebuild.php && rm run_lifecycle_rebuild.php")
print(f"OUT:\n{out}")

client.close()
