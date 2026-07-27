<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\SalarySetup;
use App\Domains\Employee\Requests\StoreEmployeeRequest;
use App\Domains\Employee\Requests\StoreSalarySetupRequest;
use App\Domains\Employee\Requests\UpdateEmployeeRequest;
use App\Domains\Employee\Resources\EmployeeResource;
use App\Domains\Employee\Resources\SalarySetupResource;
use App\Domains\Employee\Services\EmployeeActivityService;
use App\Domains\Employee\Services\EmployeeService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends BaseApiController
{
    public function __construct(private EmployeeService $service, private EmployeeActivityService $activity) {}
    public function index(Request $request) { $this->authorize('viewAny', Employee::class); return $this->paginated($this->service->paginate($request->only(['search', 'department_id', 'position_id', 'branch_id', 'employment_status', 'per_page', 'sort_by', 'sort_direction'])), EmployeeResource::class, 'Employees retrieved successfully.'); }
    public function store(StoreEmployeeRequest $request) { return $this->created(new EmployeeResource($this->service->create($request->validated())), 'Employee created successfully.'); }
    public function show(Employee $employee) { $this->authorize('view', $employee); return $this->resource(new EmployeeResource($employee->load(['branch', 'department', 'position', 'emergencyContacts', 'salarySetups' => fn ($q) => $q->orderByDesc('effective_from'), 'attachments']))); }
    public function update(UpdateEmployeeRequest $request, Employee $employee) { return $this->resource(new EmployeeResource($this->service->update($employee, $request->validated())), 'Employee updated successfully.'); }
    public function destroy(Employee $employee) { $this->authorize('delete', $employee); $this->service->delete($employee); return $this->deleted('Employee deleted successfully.'); }
    public function storeSalarySetup(StoreSalarySetupRequest $request, Employee $employee) { $salary = $employee->salarySetups()->create($request->validated() + ['created_by' => Auth::id()]); $this->activity->record('employee_salary_setup.created', $salary, $employee->id, [], $salary->getAttributes()); return $this->created(new SalarySetupResource($salary), 'Salary setup created successfully.'); }
    public function activities(Employee $employee) { $this->authorize('view', $employee); return $this->success($employee->activities()->with('actor:id,name,email')->latest()->paginate(25), 'Employee activity retrieved successfully.'); }
}
