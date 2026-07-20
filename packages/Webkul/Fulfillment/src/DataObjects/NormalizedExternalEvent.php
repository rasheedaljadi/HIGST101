<?php

namespace Webkul\Fulfillment\DataObjects;

class NormalizedExternalEvent
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public string $eventId,
        public string $externalSystem,
        public string $eventType,
        public string $resourceType,
        public string $resourceId,
        public string $occurredAt,
        public string $receivedAt,
        public string $schemaVersion,
        public ?string $correlationId = null,
        public ?string $causationId = null,
        public array $attributes = []
    ) {}

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'eventId'        => $this->eventId,
            'externalSystem' => $this->externalSystem,
            'eventType'      => $this->eventType,
            'resourceType'   => $this->resourceType,
            'resourceId'     => $this->resourceId,
            'occurredAt'     => $this->occurredAt,
            'receivedAt'     => $this->receivedAt,
            'schemaVersion'  => $this->schemaVersion,
            'correlationId'  => $this->correlationId,
            'causationId'    => $this->causationId,
            'attributes'     => $this->attributes,
        ];
    }
}
