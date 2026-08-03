<?php

namespace Webkul\Wallet\Exceptions;

use RuntimeException;

class WalletSuspendedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This wallet is suspended and cannot perform any operations.');
    }
}
