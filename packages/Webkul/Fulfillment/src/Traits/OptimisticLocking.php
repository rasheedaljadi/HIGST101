<?php

namespace Webkul\Fulfillment\Traits;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Fulfillment\Exceptions\ConcurrentUpdateException;

/**
 * This trait intentionally overrides Laravel's internal performUpdate() implementation.
 * Any Laravel framework upgrade MUST verify compatibility against the upstream implementation.
 */
trait OptimisticLocking
{
    /**
     * Perform a model update filtered by version.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     *
     * @throws \Webkul\Fulfillment\Exceptions\ConcurrentUpdateException
     */
    protected function performUpdate(Builder $query)
    {
        // Fire updating event and abort if it returns false
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        $originalVersion = $this->getOriginal('version') ?? 1;

        // Increment version
        $this->version = $originalVersion + 1;

        // Enforce the version in the WHERE clause
        $query->where('version', $originalVersion);

        // Get the dirty fields including the incremented version
        $dirty = $this->getDirty();
        $dirty['version'] = $this->version;

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
            $dirty[$this->getUpdatedAtColumn()] = $this->serializeDate($this->updated_at);
        }

        $affected = $query->update($dirty);

        if ($affected === 0) {
            throw new ConcurrentUpdateException(
                "Model updated concurrently. Version conflict for ID: " . $this->getKey()
            );
        }

        // Sync the model's attributes as original
        $this->syncChanges();
        $this->fireModelEvent('updated', false);

        return true;
    }
}
