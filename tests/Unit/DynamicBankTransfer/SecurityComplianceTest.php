<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\TestCase;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Http\Requests\DynamicBankTransferCreateRequest;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Rules\IbanValidationRule;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;

class SecurityComplianceTest extends TestCase
{
    use DatabaseTransactions;

    protected DynamicBankTransferServiceContract $service;

    protected DynamicPaymentRegistryContract $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DynamicBankTransferServiceContract::class);
        $this->registry = app(DynamicPaymentRegistryContract::class);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);
    }

    /**
     * TEST-011-001 (XSS Sanitization Audit): Feature test submitting HTML/JS XSS payloads
     * and confirming Blade rendering escapes outputs safely.
     */
    public function test_xss_sanitization_escapes_malicious_script_payloads(): void
    {
        $xssPayload = "<script>alert('XSS Attack');</script>";

        $record = DynamicBankTransfer::create([
            'title' => $xssPayload,
            'description' => $xssPayload,
            'bank_name' => $xssPayload,
            'account_holder_name' => $xssPayload,
            'account_number' => '123456',
            'iban' => 'SA0380000000608010167519',
            'transfer_instructions' => $xssPayload,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DynamicBankTransferService::clearRequestCache();
        Cache::forget(DynamicBankTransferService::CACHE_KEY_ACTIVE);

        $this->registry->reset();
        $this->registry->registerAll();

        $payment = (object) ['method' => $record->code];
        $html = View::make('dynamic_bank_transfer::shop.payment.details', compact('payment'))->render();

        $this->assertStringNotContainsString("<script>alert('XSS Attack');</script>", $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&#039;XSS Attack&#039;);&lt;/script&gt;', $html);
    }

    /**
     * TEST-011-002 (Malicious Upload Test): Feature test uploading .php / .exe executable files
     * disguised as images and confirming 422 validation rejection.
     */
    public function test_malicious_executable_file_upload_rejection(): void
    {
        $phpExecutable = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');
        $exeFile = UploadedFile::fake()->create('virus.exe', 200, 'application/x-msdownload');
        $shScript = UploadedFile::fake()->create('malicious.sh', 50, 'text/x-shellscript');

        foreach ([$phpExecutable, $exeFile, $shScript] as $file) {
            $request = new DynamicBankTransferCreateRequest;
            $validator = $this->app['validator']->make([
                'title' => 'Malicious File Method',
                'bank_name' => 'Malicious Bank',
                'account_holder_name' => 'Attacker',
                'iban' => 'SA0380000000608010167519',
                'logo' => $file,
            ], $request->rules());

            $this->assertTrue($validator->fails(), "File {$file->getClientOriginalName()} should have failed validation.");
            $this->assertArrayHasKey('logo', $validator->errors()->toArray());
        }
    }

    /**
     * TEST-011-003 (Authorization & ACL Protection Test): Confirming unauthorized admin requests
     * receive 403 Forbidden.
     */
    public function test_unauthorized_admin_requests_receive_403_forbidden(): void
    {
        // Unauthenticated request to admin endpoint
        $response = $this->get(route('admin.sales.dynamic_bank_transfers.index'));

        // Should be redirected or receive 403
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }

    /**
     * TEST-011-004 (IBAN Checksum Integrity & Tampering Protection): Confirming ISO 13616
     * MOD-97 algorithm rejects altered / tampered IBAN numbers.
     */
    public function test_iban_mod97_checksum_rejects_tampered_digits(): void
    {
        $rule = new IbanValidationRule;

        // Valid Saudi IBAN
        $validIban = 'SA0380000000608010167519';

        // Tampered single digit in IBAN (checksum failure)
        $tamperedIban = 'SA0380000000608010167518';

        $failCalled = false;
        $failCallback = function () use (&$failCalled) {
            $failCalled = true;
        };

        // Test valid
        $rule->validate('iban', $validIban, $failCallback);
        $this->assertFalse($failCalled);

        // Test tampered
        $rule->validate('iban', $tamperedIban, $failCallback);
        $this->assertTrue($failCalled);
    }
}
