<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Shared\Repositories\BaseRepository;

class SupplierPaymentRepository extends BaseRepository
{
    protected array $with = ['supplier', 'allocations.supplierInvoice'];

    public function __construct() { $this->model = new SupplierPayment(); }
}
