<?php

namespace Webkul\MobileApi\Repositories;

use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\MobileApi\Contracts\MobileApiKey;

class MobileApiKeyRepository extends Repository
{
    public function model(): string
    {
        return MobileApiKey::class;
    }

    public function generateKey(string $name, ?int $channelId = null): MobileApiKey
    {
        $key = 'pk_storefront_'.Str::random(32);

        return $this->create([
            'name' => $name,
            'key' => $key,
            'channel_id' => $channelId,
            'status' => true,
        ]);
    }

    public function findValidKey(string $key): ?MobileApiKey
    {
        return $this->findOneWhere([
            'key' => $key,
            'status' => 1,
        ]);
    }
}
