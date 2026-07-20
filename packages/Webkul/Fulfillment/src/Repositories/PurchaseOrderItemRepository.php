<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class PurchaseOrderItemRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\PurchaseOrderItem';
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
            $required = ['purchase_order_id', 'order_item_id', 'qty'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }

        if (isset($data['qty']) && (! is_numeric($data['qty']) || $data['qty'] <= 0)) {
            throw new \InvalidArgumentException("Quantity must be a positive integer.");
        }

        // Uniqueness check for composite key (purchase_order_id, order_item_id)
        if (isset($data['purchase_order_id']) && isset($data['order_item_id'])) {
            $query = $this->model->where('purchase_order_id', $data['purchase_order_id'])
                                 ->where('order_item_id', $data['order_item_id']);
            if ($id !== null) {
                $query->where('id', '!=', $id);
            }
            if ($query->exists()) {
                throw new \InvalidArgumentException("The order_item_id must be unique per purchase_order_id.");
            }
        }
    }

    /**
     * Create a new purchase order item.
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
     * Update an existing purchase order item.
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
