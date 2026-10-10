<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Models\Department;
use App\Domains\Employee\Models\Position;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeReferenceController extends BaseApiController
{
    public function departments(Request $request)
    {
        $this->authorizeAbility($request, 'reference.lookups.view');

        return $this->success(
            Department::with('parent:id,code,name')
                ->orderBy('name')
                ->get()
        );
    }

    public function positions(Request $request)
    {
        $this->authorizeAbility($request, 'reference.lookups.view');

        return $this->success(
            Position::with('department:id,code,name')
                ->when(
                    $request->department_id,
                    fn($q, $id) => $q->where('department_id', $id)
                )
                ->orderBy('name')
                ->get()
        );
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeAbility($request, 'reference.lookups.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:employee_departments,code'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:employee_departments,id'],
            'is_active' => ['boolean'],
        ]);

        return $this->created(
            Department::create($data),
            'Department created successfully.'
        );
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $this->authorizeAbility($request, 'reference.lookups.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('employee_departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:employee_departments,id', Rule::notIn([$department->id])],
            'is_active' => ['boolean'],
        ]);

        $department->update($data);

        return $this->resource(
            $department,
            'Department updated successfully.'
        );
    }

    public function storePosition(Request $request)
    {
        $this->authorizeAbility($request, 'reference.lookups.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:employee_positions,code'],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:employee_departments,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return $this->created(
            Position::create($data),
            'Position created successfully.'
        );
    }

    public function updatePosition(Request $request, Position $position)
    {
        $this->authorizeAbility($request, 'reference.lookups.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('employee_positions', 'code')->ignore($position)],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:employee_departments,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $position->update($data);

        return $this->resource(
            $position,
            'Position updated successfully.'
        );
    }
}