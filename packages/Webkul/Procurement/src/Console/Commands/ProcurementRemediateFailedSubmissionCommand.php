<?php

namespace Webkul\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Procurement\Services\ProcurementExternalRemediationService;

class ProcurementRemediateFailedSubmissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'procurement:remediate-failed-submission 
                            {spo_id : The ID of the Supplier Purchase Order to remediate}
                            {--actor_id=1 : Admin User ID executing the remediation}
                            {--error_code=IllegalAccessToken : Error code returned by provider}
                            {--error_msg=The specified API Path or access token is invalid or ungranted on AliExpress IOP gateway : Error message}
                            {--request_id= : Provider Request ID for audit tracing}
                            {--synthetic_id= : The synthetic identifier to reject}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remediate a Supplier Purchase Order with an unverified or synthetic external order ID';

    /**
     * Execute the console command.
     */
    public function handle(ProcurementExternalRemediationService $remediationService): int
    {
        $spoId = (int) $this->argument('spo_id');
        $actorId = (int) $this->option('actor_id');
        $errorCode = (string) $this->option('error_code');
        $errorMsg = (string) $this->option('error_msg');
        $requestId = $this->option('request_id');
        $syntheticId = $this->option('synthetic_id');

        $this->info("Remediating Supplier Purchase Order #{$spoId}...");

        try {
            $spo = $remediationService->markFailedExternalSubmission(
                $spoId,
                $actorId,
                $errorCode,
                $errorMsg,
                $requestId,
                $syntheticId
            );

            $this->info("Successfully remediated SPO #{$spo->id}. New state: {$spo->state}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Remediation failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
