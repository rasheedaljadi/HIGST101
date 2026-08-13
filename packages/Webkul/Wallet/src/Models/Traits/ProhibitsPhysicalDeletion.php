<?php

namespace Webkul\Wallet\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait ProhibitsPhysicalDeletion
{
    /**
     * Boot the trait to prevent single model instance deletion.
     */
    public static function bootProhibitsPhysicalDeletion(): void
    {
        static::deleting(function ($model) {
            throw new LogicException('Physical deletion of '.class_basename($model).' records is strictly forbidden to preserve immutable financial and audit history.');
        });
    }

    /**
     * Create a new Eloquent query builder that intercepts and forbids bulk/direct Query Builder deletes.
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            /**
             * Intercept and reject direct Query Builder deletion.
             *
             * @throws LogicException
             */
            public function delete()
            {
                throw new LogicException('Direct Query Builder physical deletion (e.g. query()->delete() or where()->delete()) of '.class_basename($this->getModel()).' records is strictly forbidden. Use status archiving or state transitions instead.');
            }
        };
    }
}
