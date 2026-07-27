<?php

namespace App\Domains\Employee\Policies;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Services\EmployeeAuthorizationService;
use App\Models\User;

class EmployeePolicy
{
    public function __construct(private EmployeeAuthorizationService $authorization) {}
    public function viewAny(User $user): bool { return $this->authorization->allows($user, 'employee.view'); }
    public function view(User $user, Employee $employee): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->authorization->allows($user, 'employee.create'); }
    public function update(User $user, Employee $employee): bool { return $this->authorization->allows($user, 'employee.update'); }
    public function delete(User $user, Employee $employee): bool { return $this->authorization->allows($user, 'employee.delete'); }
    public function manageTransactions(User $user): bool { return $this->authorization->allows($user, 'employee.transactions.manage'); }
    public function viewReports(User $user): bool { return $this->authorization->allows($user, 'employee.reports.view'); }
}
