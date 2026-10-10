<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\Department;
use App\Domains\Shared\Repositories\BaseRepository;

class DepartmentRepository extends BaseRepository
{
    protected array $with = ['parent'];
    public function __construct() { $this->model = new Department(); }
}
