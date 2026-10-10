<?php

namespace App\Domains\Finance\Controllers;

use App\Domains\Finance\Requests\StoreExpenseRequest;
use App\Domains\Finance\Resources\ExpenseResource;
use App\Domains\Finance\Services\ExpenseService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    public function __construct(private ExpenseService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'finance.expenses.view');
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'category' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->paginated(
            $this->service->paginate($filters),
            ExpenseResource::class,
            'Expenses retrieved successfully.'
        );
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->authorizeAbility($request, 'finance.expenses.create');

        return $this->created(
            new ExpenseResource($this->service->create($request->validated(), $request->user()->id)),
            'Expense recorded successfully.'
        );
    }
}
