<?php

namespace App\Domains\Sales\Exceptions;

use InvalidArgumentException;

class SaleAlreadyCompleted extends InvalidArgumentException
{
    public function __construct(public readonly int $salesOrderId)
    {
        parent::__construct('This sales order is already completed.');
    }
}
