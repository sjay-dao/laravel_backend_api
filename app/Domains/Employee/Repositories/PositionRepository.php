<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\Position;
use App\Domains\Shared\Repositories\BaseRepository;

class PositionRepository extends BaseRepository
{
    protected array $with = ['department'];
    public function __construct() { $this->model = new Position(); }
}
