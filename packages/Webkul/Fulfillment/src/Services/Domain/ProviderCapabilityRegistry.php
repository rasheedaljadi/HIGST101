<?php

namespace Webkul\Fulfillment\Services\Domain;

class ProviderCapabilityRegistry
{
    protected array $capabilities = [
        'aliexpress' => [
            'AliExpress Contract v2026-07' => [
                'supportsSplitShipment'   => true,
                'supportsCancel'          => true,
                'supportsPartialRefund'   => true,
                'supportsTrackingWebhook' => true,
                'supportsInvoice'         => true,
                'supportsCOD'             => false,
            ],
            'AliExpress Contract v2026-08' => [
                'supportsSplitShipment'   => true,
                'supportsCancel'          => true,
                'supportsPartialRefund'   => true,
                'supportsTrackingWebhook' => true,
                'supportsInvoice'         => true,
                'supportsCOD'             => true,
            ]
        ],
        'cj_dropshipping' => [
            'CJ Contract v1' => [
                'supportsSplitShipment'   => false,
                'supportsCancel'          => true,
                'supportsPartialRefund'   => false,
                'supportsTrackingWebhook' => false,
                'supportsInvoice'         => false,
                'supportsCOD'             => false,
            ]
        ]
    ];

    public function getCapabilities(string $providerCode, string $contractVersion): array
    {
        return $this->capabilities[$providerCode][$contractVersion] 
            ?? $this->capabilities[$providerCode]['AliExpress Contract v2026-07'] 
            ?? [
                'supportsSplitShipment'   => false,
                'supportsCancel'          => false,
                'supportsPartialRefund'   => false,
                'supportsTrackingWebhook' => false,
                'supportsInvoice'         => false,
                'supportsCOD'             => false,
            ];
    }
}
