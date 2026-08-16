<?php

namespace Webkul\Sales\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Sales\Services\UnclosedOrderReminderService;

class CheckUnclosedOrdersReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-unclosed-reminders
                            {--interval= : Custom reminder interval in days (default: from settings or 5)}
                            {--dry-run : Simulate execution without creating notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan unclosed orders and generate admin reminder notifications at periodic milestones (e.g., every 5 days).';

    /**
     * Execute the console command.
     */
    public function handle(UnclosedOrderReminderService $reminderService): int
    {
        $intervalOption = $this->option('interval');
        $interval = $intervalOption ? (int) $intervalOption : null;
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting unclosed orders reminder scan...');

        $result = $reminderService->processReminders($interval, $dryRun);

        if (($result['status'] ?? '') === 'skipped') {
            $this->warn('Unclosed order reminders skipped: '.($result['reason'] ?? 'Disabled in settings'));

            return Command::SUCCESS;
        }

        $scanned = $result['scanned_orders'] ?? 0;
        $created = $result['created_notifications'] ?? 0;
        $intervalUsed = $result['interval_days'] ?? 5;

        $this->info("Scanned {$scanned} unclosed orders using {$intervalUsed}-day interval.");

        if ($dryRun) {
            $this->warn("[DRY RUN] Would create {$created} reminder notifications.");
        } else {
            $this->info("Successfully created {$created} reminder notifications.");
        }

        if (! empty($result['details'])) {
            $headers = ['Order ID', 'Status', 'Days Passed', 'Milestone', 'Notified'];
            $rows = array_map(function ($item) {
                return [
                    '#'.$item['order_id'],
                    $item['status'] ?? '-',
                    $item['days_passed'] ?? '-',
                    ($item['milestone_day'] ?? '-').' days',
                    $item['notified'] ? 'YES' : 'Already notified',
                ];
            }, $result['details']);

            $this->table($headers, $rows);
        }

        return Command::SUCCESS;
    }
}
