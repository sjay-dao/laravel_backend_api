<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Requests\StoreEmployeeScheduleAssignmentRequest;
use App\Domains\Employee\Requests\UpdateEmployeeScheduleAssignmentRequest;
use App\Domains\Employee\Resources\EmployeeScheduleAssignmentResource;
use App\Domains\Employee\Services\EmployeeScheduleAssignmentService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class EmployeeScheduleAssignmentController extends BaseApiController
{
    public function __construct(protected EmployeeScheduleAssignmentService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'employee.schedule.assign');

        return $this->success(
            EmployeeScheduleAssignmentResource::collection(
                $this->service->paginate()
            ),
            'Schedule assignments retrieved successfully.'
        );
    }

    public function show(Request $request, int $assignment)
    {
        $this->authorizeAbility($request, 'employee.schedule.assign');

        return $this->resource(
            new EmployeeScheduleAssignmentResource(
                $this->service->find($assignment)
            ),
            'Schedule assignment retrieved successfully.'
        );
    }

    public function store(StoreEmployeeScheduleAssignmentRequest $request)
    {
        $this->authorizeAbility($request, 'employee.schedule.assign');

        return $this->created(
            new EmployeeScheduleAssignmentResource(
                $this->service->create(
                    $request->validated(),
                    $request->user()
                )
            ),
            'Schedule assignment created successfully.'
        );
    }

    public function update(UpdateEmployeeScheduleAssignmentRequest $request, int $assignment)
    {
        $this->authorizeAbility($request, 'employee.schedule.assign');

        return $this->resource(
            new EmployeeScheduleAssignmentResource(
                $this->service->update(
                    $assignment,
                    $request->validated()
                )
            ),
            'Schedule assignment updated successfully.'
        );
    }

    public function destroy(Request $request, int $assignment)
    {
        $this->authorizeAbility($request, 'employee.schedule.assign');

        $this->service->delete($assignment);

        return $this->deleted(
            'Schedule assignment deleted successfully.'
        );
    }
}