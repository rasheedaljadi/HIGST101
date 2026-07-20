<?php

namespace Webkul\Fulfillment\Services\Application;

use Webkul\Fulfillment\Models\ProcurementSession;

class ProcurementReplayService
{
    /**
     * Compute a detailed diff between an old snapshot and a new snapshot.
     */
    public function diff(array $oldSnapshot, array $newSnapshot): array
    {
        $diff = [];

        foreach ($newSnapshot as $key => $val) {
            $oldVal = $oldSnapshot[$key] ?? null;

            if ($oldVal !== $val) {
                $diff[$key] = [
                    'old' => $oldVal,
                    'new' => $val,
                ];
            }
        }

        return $diff;
    }

    /**
     * Replay a procurement session execution from a given state.
     */
    public function replay(ProcurementSession $session, string $startFromState): void
    {
        $session->transitionTo($startFromState);
    }
}
