<?php

namespace Webkul\Fulfillment\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProcessedEventRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Fulfillment\Contracts\ProcessedEvent';
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
            $required = ['provider', 'event_id', 'event_name'];
            foreach ($required as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required.");
                }
            }
        }
    }

    /**
     * Create a new processed event entry.
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
     * Update an existing processed event entry.
     *
     * @param  array  $attributes
     * @param  string  $id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $this->validateData($attributes, $id);

        return parent::update($attributes, $id);
    }
}
