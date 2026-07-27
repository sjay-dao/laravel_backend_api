<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\Employee;
use App\Domains\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository extends BaseRepository
{
    protected array $with = ['branch', 'department', 'position'];

    public function __construct() { $this->model = new Employee(); }

    public function search(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $sortBy = in_array($filters['sort_by'] ?? null, ['employee_no', 'first_name', 'last_name', 'hire_date', 'employment_status'], true) ? $filters['sort_by'] : 'last_name';
        $sortDirection = ($filters['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $this->query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('employee_no', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['department_id'] ?? null, fn ($query, $value) => $query->where('department_id', $value))
            ->when($filters['position_id'] ?? null, fn ($query, $value) => $query->where('position_id', $value))
            ->when($filters['branch_id'] ?? null, fn ($query, $value) => $query->where('branch_id', $value))
            ->when($filters['employment_status'] ?? null, fn ($query, $value) => $query->where('employment_status', $value))
            ->orderBy($sortBy, $sortDirection)->orderBy('first_name')->paginate($perPage);
    }
}
