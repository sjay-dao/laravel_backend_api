<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\EmployeeTransaction;
use App\Domains\Employee\Requests\StoreEmployeeTransactionRequest;
use App\Domains\Employee\Resources\EmployeeTransactionResource;
use App\Domains\Employee\Services\EmployeeTransactionService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class EmployeeTransactionController extends BaseApiController
{
    public function __construct(private EmployeeTransactionService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'employee.transactions.view');

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $transactions = EmployeeTransaction::with('employee:id,employee_no,first_name,last_name')
            ->when($request->employee_id, fn($q, $id) => $q->where('employee_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->paginated($transactions, EmployeeTransactionResource::class, 'Employee transactions retrieved successfully.');
    }

    public function store(StoreEmployeeTransactionRequest $request)
    {
        $this->authorizeAbility($request, 'employee.transactions.create');

        return $this->created(
            new EmployeeTransactionResource(
                $this->service->create($request->validated())
            ),
            'Employee transaction posted successfully.'
        );
    }

    public function show(Request $request, EmployeeTransaction $employeeTransaction)
    {
        $this->authorizeAbility($request, 'employee.transactions.view');

        return $this->resource(
            new EmployeeTransactionResource(
                $employeeTransaction->load('employee')
            )
        );
    }

    public function void(Request $request, EmployeeTransaction $employeeTransaction)
    {
        $this->authorizeAbility($request, 'employee.transactions.update');

        return $this->resource(
            new EmployeeTransactionResource(
                $this->service->void($employeeTransaction)
            ),
            'Employee transaction voided successfully.'
        );
    }

    public function ledger(Request $request, Employee $employee)
    {
        $this->authorizeAbility($request, 'employee.transactions.view');

        return $this->success(
            $this->service->ledger($employee->id),
            'Employee ledger retrieved successfully.'
        );
    }
}