<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\Unit;
use App\Domains\Shared\Repositories\BaseRepository;

class UnitRepository extends BaseRepository
{
    protected array $with = [];

    public function __construct()
    {
        $this->model = new Unit();
    }
}