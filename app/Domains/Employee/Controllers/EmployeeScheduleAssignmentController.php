<?php

namespace App\Domains\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Domains\Employee\Requests\StoreEmployeeScheduleAssignmentRequest;
use App\Domains\Employee\Requests\UpdateEmployeeScheduleAssignmentRequest;

use App\Domains\Employee\Services\EmployeeScheduleAssignmentService;

use App\Domains\Employee\Resources\EmployeeScheduleAssignmentResource;

class EmployeeScheduleAssignmentController extends Controller
{
    public function __construct(
        protected EmployeeScheduleAssignmentService $service
    ) {
    }

    public function index()
    {
        return EmployeeScheduleAssignmentResource::collection(
            $this->service->paginate()
        );
    }

    public function show(int $assignment)
    {
        return new EmployeeScheduleAssignmentResource(
            $this->service->find($assignment)
        );
    }

    public function store(StoreEmployeeScheduleAssignmentRequest $request)
    {
        $assignment = $this->service->create(
            $request->validated(),
            $request->user()
        );

        return new EmployeeScheduleAssignmentResource(
            $assignment
        );
    }

    public function update(
        UpdateEmployeeScheduleAssignmentRequest $request,
        int $assignment
    ) {
        $record = $this->service->update(
            $assignment,
            $request->validated()
        );

        return new EmployeeScheduleAssignmentResource(
            $record
        );
    }

    public function destroy(int $assignment)
    {
        $this->service->delete($assignment);

        return response()->json([
            'message' => 'Assignment deleted.'
        ]);
    }
}