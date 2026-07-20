<?php

namespace Webkul\Fulfillment\Enums;

enum SyncStrategy: string
{
    case FULL = 'FULL';
    case INCREMENTAL = 'INCREMENTAL';
    case RECOVERY = 'RECOVERY';
    case VERIFY_ONLY = 'VERIFY_ONLY';
}
