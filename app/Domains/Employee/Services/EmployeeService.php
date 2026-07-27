<?php

namespace App\Domains\Employee\Services;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Repositories\EmployeeRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(private EmployeeRepository $employees, private EmployeeActivityService $activity) {}
    public function paginate(array $filters) { return $this->employees->search($filters); }
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $contacts = Arr::pull($data, 'emergency_contacts', []);
            $employee = $this->employees->create($data + ['created_by' => Auth::id(), 'updated_by' => Auth::id()]);
            $this->syncContacts($employee, $contacts);
            $this->activity->record('employee.created', $employee, $employee->id, [], $employee->getAttributes());
            return $employee->load(['branch', 'department', 'position', 'emergencyContacts']);
        });
    }
    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $contacts = Arr::pull($data, 'emergency_contacts', null);
            $old = $employee->getOriginal();
            $employee = $this->employees->update($employee, $data + ['updated_by' => Auth::id()]);
            if ($contacts !== null) { $employee->emergencyContacts()->delete(); $this->syncContacts($employee, $contacts); }
            $this->activity->record('employee.updated', $employee, $employee->id, $old, $employee->getChanges());
            return $employee->load(['branch', 'department', 'position', 'emergencyContacts']);
        });
    }
    public function delete(Employee $employee): void { DB::transaction(function () use ($employee) { $this->activity->record('employee.deleted', $employee, $employee->id, $employee->getAttributes()); $employee->delete(); }); }
    private function syncContacts(Employee $employee, array $contacts): void
    {
        foreach ($contacts as $index => $contact) { $employee->emergencyContacts()->create($contact + ['is_primary' => (bool) ($contact['is_primary'] ?? $index === 0)]); }
    }
}
