<?php

use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;

uses(TestCase::class, AdminTestBench::class);

it('renders the import page with the streaming progress script', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.dropshipping.import.index'));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('ae-import-btn')
        ->and($html)->toContain('dropshipping/import/stream')
        ->and($html)->toContain('EventSource')
        ->and($html)->toContain('startImport');
});
