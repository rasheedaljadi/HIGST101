<?php

namespace Webkul\OfflinePayments\Services;

class OfflinePaymentSnapshotReader
{
    /**
     * Read and normalize snapshot payload across schema versions (V1 & V2).
     */
    public function read(?array $snapshot): array
    {
        if (! $snapshot) {
            return [];
        }

        $version = $snapshot['schema_version'] ?? 1;

        if ($version >= 2) {
            $account = $snapshot['account'] ?? [];
            $destination = $snapshot['destination'] ?? [];
            $currency = $snapshot['currency'] ?? [];

            return [
                'schema_version' => $version,
                'snapshot_type' => $snapshot['snapshot_type'] ?? 'offline_payment',
                'account_display_name' => $account['display_name'] ?? '',
                'account_provider_name' => $account['provider_name'] ?? '',
                'account_recipient_name' => $account['recipient_name'] ?? '',
                'account_logo_path' => $account['logo_path'] ?? null,
                'account_identifier' => $destination['account_identifier'] ?? '',
                'swift_code' => $destination['swift_code'] ?? null,
                'transfer_instructions' => $destination['transfer_instructions'] ?? null,
                'currency_code' => $currency['code'] ?? '',
                'currency_name' => $currency['name'] ?? '',
            ];
        }

        // Fallback for V1 legacy snapshots
        return [
            'schema_version' => 1,
            'snapshot_type' => 'offline_payment',
            'account_display_name' => $snapshot['display_name'] ?? '',
            'account_provider_name' => $snapshot['provider_name'] ?? '',
            'account_recipient_name' => $snapshot['recipient_name'] ?? '',
            'account_logo_path' => $snapshot['logo_path'] ?? null,
            'account_identifier' => $snapshot['account_identifier'] ?? '',
            'swift_code' => null,
            'transfer_instructions' => $snapshot['transfer_instructions'] ?? null,
            'currency_code' => $snapshot['currency_code'] ?? '',
            'currency_name' => '',
        ];
    }
}
