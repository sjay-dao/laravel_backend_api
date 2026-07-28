<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\EmployeeScheduleAssignment;

class EmployeeScheduleAssignmentRepository
{
    public function paginate(int $perPage = 15)
    {
        return EmployeeScheduleAssignment::with([
            'employee',
            'schedule',
            'assignedBy'
        ])->paginate($perPage);
    }

    public function find(int $id): EmployeeScheduleAssignment
    {
        return EmployeeScheduleAssignment::with([
            'employee',
            'schedule',
            'assignedBy'
        ])->findOrFail($id);
    }

    public function create(array $data): EmployeeScheduleAssignment
    {
        return EmployeeScheduleAssignment::create($data)
            ->load([
                'employee',
                'schedule',
                'assignedBy'
            ]);
    }

    public function update(
        EmployeeScheduleAssignment $assignment,
        array $data
    ): EmployeeScheduleAssignment {

        $assignment->update($data);

        return $assignment->fresh([
            'employee',
            'schedule',
            'assignedBy'
        ]);
    }

    public function delete(EmployeeScheduleAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function activeAssignment(int $employeeId)
    {
        return EmployeeScheduleAssignment::active()
            ->where('employee_id', $employeeId)
            ->first();
    }
}