<?php

namespace App\Domains\Inventory\DataTransferObjects;

class InventoryStock
{
    public function __construct(

        public readonly float $onHand,

        public readonly float $reserved = 0,

        public readonly float $incoming = 0,

        public readonly float $outgoing = 0

    ) {}

    public function available(): float
    {
        return $this->onHand - $this->reserved;
    }
}