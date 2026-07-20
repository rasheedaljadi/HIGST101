<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class AllocationLogRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\AllocationLog';
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
            $required = ['order_allocation_id', 'action', 'old_qty', 'new_qty'];
            foreach ($required as $field) {
                if (! isset($data[$field]) && ! array_key_exists($field, $data)) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }
    }

    /**
     * Create a new allocation log.
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
     * Update an existing allocation log.
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
