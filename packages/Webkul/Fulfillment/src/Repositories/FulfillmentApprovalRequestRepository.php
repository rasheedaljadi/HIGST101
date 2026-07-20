<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class FulfillmentApprovalRequestRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\FulfillmentApprovalRequest';
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
            $required = ['purchase_order_id', 'requested_by', 'action', 'reason'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }

        if (isset($data['status']) && ! in_array($data['status'], ['pending', 'approved', 'rejected', 'executed'], true)) {
            throw new \InvalidArgumentException("Invalid status: {$data['status']}. Must be pending, approved, rejected, or executed.");
        }
    }

    /**
     * Create a new approval request.
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
     * Update an existing approval request.
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
