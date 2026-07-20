<?php

namespace Webkul\Fulfillment\Services\Domain;

class EventSchemaRegistry
{
    protected array $schemas = [
        'ProcurementStarted' => [
            1 => [
                'deserialize' => 'deserializeV1',
            ]
        ]
    ];

    public function deserialize(string $eventName, int $version, array $payload): array
    {
        $handler = $this->schemas[$eventName][$version]['deserialize'] ?? null;
        if ($handler && method_exists($this, $handler)) {
            return $this->$handler($payload);
        }
        return $payload;
    }

    protected function deserializeV1(array $payload): array
    {
        return $payload;
    }
}
