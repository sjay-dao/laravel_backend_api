<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\EmployeeTransaction;
use App\Domains\Employee\Resources\EmployeeResource;
use App\Domains\Employee\Resources\EmployeeTransactionResource;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class EmployeeReportController extends BaseApiController
{
    public function employees(Request $request) { $this->authorize('viewReports', Employee::class); return $this->paginated(Employee::with(['department', 'position', 'branch'])->orderBy('last_name')->paginate(min(max((int) $request->input('per_page', 50), 1), 100)), EmployeeResource::class, 'Employee report retrieved successfully.'); }
    public function transactions(Request $request, string $type) { $this->authorize('viewReports', Employee::class); abort_unless(in_array($type, ['advance', 'deduction'], true), 404); $rows = EmployeeTransaction::with('employee')->where('type', $type)->where('status', 'posted')->orderByDesc('transaction_date')->paginate(min(max((int) $request->input('per_page', 50), 1), 100)); return $this->paginated($rows, EmployeeTransactionResource::class, ucfirst($type) . ' report retrieved successfully.'); }
    public function salaryHistory(Employee $employee) { $this->authorize('viewReports', Employee::class); return $this->success($employee->salarySetups()->orderByDesc('effective_from')->get(), 'Salary history retrieved successfully.'); }
}
