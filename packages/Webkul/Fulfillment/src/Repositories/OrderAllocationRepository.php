<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class OrderAllocationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\OrderAllocation';
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
        if ($id === null) {
            $required = ['order_id', 'order_item_id', 'allocation_type', 'source_code'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }

        if (isset($data['state']) && ! in_array($data['state'], ['reserved', 'fulfilled', 'canceled'], true)) {
            throw new \InvalidArgumentException("Invalid state: {$data['state']}. Must be reserved, fulfilled, or canceled.");
        }
    }

    /**
     * Create a new order allocation.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        $this->validateData($attributes);

        return parent::create($attributes);
    }

    /**
     * Update an existing order allocation.
     *
     * @param  array  $attributes
     * @param  int  $id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $this->validateData($attributes, $id);

        return parent::update($attributes, $id);
    }
}
