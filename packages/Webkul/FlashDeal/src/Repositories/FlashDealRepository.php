<?php

namespace Webkul\FlashDeal\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Webkul\Core\Eloquent\Repository;
use Webkul\FlashDeal\Contracts\FlashDeal;

class FlashDealRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return FlashDeal::class;
    }

    /**
     * Get active flash deals with loaded products.
     */
    public function getActiveDeals(): Collection
    {
        $now = Carbon::now();

        return $this->model
            ->where('status', 1)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->with(['products' => function ($query) use ($now) {
                $query->whereHas('product', function ($pQuery) {
                    $pQuery->where('status', 1);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('offer_end_time')
                        ->orWhere('offer_end_time', '>', $now);
                })
                    ->orderByRaw('COALESCE(offer_end_time, "9999-12-31") ASC')
                    ->orderBy('sort_order', 'asc')
                    ->with(['product']);
            }])
            ->orderBy('starts_at', 'asc')
            ->get();
    }
}
