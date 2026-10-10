<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryCategory;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryCategoryRepository extends BaseRepository
{
    protected array $with = [];

    public function __construct()
    {
        $this->model = new InventoryCategory();
    }
}