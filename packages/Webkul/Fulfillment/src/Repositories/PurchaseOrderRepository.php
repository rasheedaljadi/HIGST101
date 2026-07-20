<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Fulfillment\Models\PurchaseOrder;

class PurchaseOrderRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\PurchaseOrder';
    }

    /**
     * Validate data before creation or update.
     *
     * @param  array  $data
     * @param  int|null  $id
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validateData(array $data, ?int $id = null): void
    {
        // 1. Required fields for creation
        if ($id === null) {
            $required = ['order_id', 'provider', 'idempotency_key', 'internal_reference'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }

        // 2. Validate idempotency_key if set
        if (isset($data['idempotency_key'])) {
            if (! preg_match('/^[a-f0-9]{64}$/i', $data['idempotency_key'])) {
                throw new \InvalidArgumentException("Invalid idempotency_key format. Must be a SHA-256 hex string (64 characters).");
            }

            // Uniqueness check
            $query = $this->model->where('idempotency_key', $data['idempotency_key']);
            if ($id !== null) {
                $query->where('id', '!=', $id);
            }
            if ($query->exists()) {
                throw new \InvalidArgumentException("The idempotency_key must be unique.");
            }
        }

        // 3. Validate internal_reference uniqueness if set
        if (isset($data['internal_reference'])) {
            $query = $this->model->where('internal_reference', $data['internal_reference']);
            if ($id !== null) {
                $query->where('id', '!=', $id);
            }
            if ($query->exists()) {
                throw new \InvalidArgumentException("The internal_reference must be unique.");
            }
        }

        // 4. Validate state if set
        if (isset($data['state'])) {
            $validStates = [
                PurchaseOrder::STATE_PENDING,
                PurchaseOrder::STATE_SUBMITTING,
                PurchaseOrder::STATE_SUBMITTED,
                PurchaseOrder::STATE_SHIPPED,
                PurchaseOrder::STATE_DELIVERED,
                PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                PurchaseOrder::STATE_CANCELED,
                PurchaseOrder::STATE_AWAITING_PAYMENT,
            ];

            if (! in_array($data['state'], $validStates, true)) {
                throw new \InvalidArgumentException("Invalid state: {$data['state']}.");
            }
        }
    }

    /**
     * Create a new purchase order.
     *
     * @param  array  $attributes
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function create(array $attributes)
    {
        $this->validateData($attributes);

        return parent::create($attributes);
    }

    /**
     * Update an existing purchase order.
     *
     * @param  array  $attributes
     * @param  int  $id
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function update(array $attributes, $id)
    {
        $this->validateData($attributes, $id);

        return parent::update($attributes, $id);
    }
}
