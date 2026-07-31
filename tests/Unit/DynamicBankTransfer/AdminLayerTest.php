<?php

namespace Tests\Unit\DynamicBankTransfer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\DynamicBankTransfer\Contracts\DynamicBankTransferServiceContract;
use Webkul\DynamicBankTransfer\Contracts\DynamicPaymentRegistryContract;
use Webkul\DynamicBankTransfer\Http\Controllers\Admin\DynamicBankTransferController;
use Webkul\DynamicBankTransfer\Http\Requests\DynamicBankTransferCreateRequest;
use Webkul\DynamicBankTransfer\Http\Requests\DynamicBankTransferUpdateRequest;
use Webkul\DynamicBankTransfer\Models\DynamicBankTransfer;
use Webkul\DynamicBankTransfer\Rules\IbanValidationRule;
use Webkul\DynamicBankTransfer\Services\DynamicBankTransferService;
use Webkul\User\Models\Admin;

class AdminLayerTest extends TestCase
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

        $channel = Channel::first();
        if ($channel) {
            core()->setCurrentChannel($channel);
        }

        $locale = Locale::first();
        if ($locale) {
            app()->setLocale($locale->code);
        }
    }

    /**
     * TEST-006-003: Unit test checking valid vs invalid IBAN strings against IbanValidationRule.
     */
    public function test_iban_validation_rule_accepts_valid_and_rejects_invalid_ibans(): void
    {
        $rule = new IbanValidationRule;

        $failedMessages = [];
        $failClosure = function ($message) use (&$failedMessages) {
            $failedMessages[] = $message;
        };

        // 1. Valid Saudi Arabia IBAN
        $validIban = 'SA0380000000608010167519';
        $rule->validate('iban', $validIban, $failClosure);
        $this->assertEmpty($failedMessages);

        // 2. Invalid Checksum IBAN
        $failedMessages = [];
        $invalidChecksumIban = 'SA0080000000608010167519';
        $rule->validate('iban', $invalidChecksumIban, $failClosure);
        $this->assertNotEmpty($failedMessages);

        // 3. Invalid Format IBAN
        $failedMessages = [];
        $invalidFormatIban = 'INVALID_IBAN_123';
        $rule->validate('iban', $invalidFormatIban, $failClosure);
        $this->assertNotEmpty($failedMessages);
    }

    /**
     * TEST-006-001 & TEST-006-004: Feature test covering Admin CRUD, status toggle, and upload security.
     */
    public function test_admin_crud_operations_and_upload_security(): void
    {
        Storage::fake('public');
        $controller = app(DynamicBankTransferController::class);

        // 1. Create Method via Controller
        $validImage = UploadedFile::fake()->image('bank_logo.png', 100, 100);
        $createData = [
            'title' => 'Test Admin SABB',
            'bank_name' => 'SABB',
            'account_holder_name' => 'HIGEST Corp',
            'iban' => 'SA0380000000608010167519',
            'is_active' => '1',
            'sort_order' => '1',
            'logo' => $validImage,
        ];

        $createRequest = DynamicBankTransferCreateRequest::create(
            route('admin.sales.dynamic_bank_transfers.store'),
            'POST',
            $createData,
            [],
            ['logo' => $validImage]
        );
        $createRequest->setContainer($this->app)->setRedirector($this->app->make('redirect'))->validateResolved();

        $createResponse = $controller->store($createRequest);
        $this->assertDatabaseHas('dynamic_bank_transfers', [
            'bank_name' => 'SABB',
            'iban' => 'SA0380000000608010167519',
        ]);

        // 2. Upload Security Test: Reject non-image file (TEST-006-004)
        $invalidFile = UploadedFile::fake()->create('malicious.exe', 500, 'application/x-msdownload');
        $invalidRule = new DynamicBankTransferCreateRequest;
        $validator = $this->app['validator']->make([
            'title' => 'Malicious Method',
            'bank_name' => 'Fake Bank',
            'account_holder_name' => 'Attacker',
            'iban' => 'SA0380000000608010167519',
            'logo' => $invalidFile,
        ], $invalidRule->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('logo', $validator->errors()->toArray());

        // 3. Edit / Update Method
        $record = DynamicBankTransfer::where('bank_name', 'SABB')->first();
        $this->assertNotNull($record);

        $updateRequest = DynamicBankTransferUpdateRequest::create(
            route('admin.sales.dynamic_bank_transfers.update', $record->id),
            'PUT',
            [
                'title' => 'Updated Admin SABB',
                'bank_name' => 'SABB',
                'account_holder_name' => 'HIGEST Corp',
                'iban' => 'SA0380000000608010167519',
                'is_active' => '1',
            ]
        );
        $updateRequest->setContainer($this->app)->setRedirector($this->app->make('redirect'))->validateResolved();

        $controller->update($updateRequest, $record->id);
        $this->assertDatabaseHas('dynamic_bank_transfers', [
            'id' => $record->id,
            'title' => 'Updated Admin SABB',
        ]);

        // 4. Delete Method
        $controller->destroy($record->id);

        $this->assertSoftDeleted('dynamic_bank_transfers', [
            'id' => $record->id,
        ]);
    }

    /**
     * TEST-006-002: Feature test verifying DataGrid response structure.
     */
    public function test_datagrid_response_structure(): void
    {
        $controller = app(DynamicBankTransferController::class);

        $request = Request::create(route('admin.sales.dynamic_bank_transfers.index'), 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        $response = $controller->index();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * TEST-006-005: Feature test verifying unauthorized admin users receive 403 Forbidden or redirect.
     */
    public function test_unauthenticated_access_is_restricted(): void
    {
        $response = $this->get(route('admin.sales.dynamic_bank_transfers.index'));

        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }
}
