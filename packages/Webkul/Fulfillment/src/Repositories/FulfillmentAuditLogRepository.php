<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class FulfillmentAuditLogRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\FulfillmentAuditLog';
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
            $required = ['user_id', 'action'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }

        if (isset($data['action']) && ! in_array($data['action'], ['retry', 'cancel', 'state_override', 'edit'], true)) {
            throw new \InvalidArgumentException("Invalid action: {$data['action']}. Must be retry, cancel, state_override, or edit.");
        }
    }

    /**
     * Create a new audit log.
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
     * Update an existing audit log.
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
