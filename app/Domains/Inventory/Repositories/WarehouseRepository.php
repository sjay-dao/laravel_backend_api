<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Shared\Repositories\BaseRepository;

class WarehouseRepository extends BaseRepository
{
    protected array $with = [];

    public function __construct()
    {
        $this->model = new Warehouse();
    }
}