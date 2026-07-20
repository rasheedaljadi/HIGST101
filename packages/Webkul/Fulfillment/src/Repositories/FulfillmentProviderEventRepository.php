<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class FulfillmentProviderEventRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\FulfillmentProviderEvent';
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
            $required = ['purchase_order_id', 'provider', 'external_state', 'source_type', 'payload', 'received_at'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }
    }

    /**
     * Create a new provider event.
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
     * Update an existing provider event.
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
