<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Requests\StoreScheduleRequest;
use App\Domains\Employee\Requests\UpdateScheduleRequest;
use App\Domains\Employee\Resources\ScheduleResource;
use App\Domains\Employee\Services\ScheduleService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class ScheduleController extends BaseApiController
{
    public function __construct(protected ScheduleService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'employee.schedules.view');

        return $this->success(
            ScheduleResource::collection(
                $this->service->index($request->all())
            )
        );
    }

    public function show(Request $request, int $schedule)
    {
        $this->authorizeAbility($request, 'employee.schedules.view');

        return $this->resource(
            new ScheduleResource(
                $this->service->find($schedule)
            )
        );
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorizeAbility($request, 'employee.schedules.create');

        return $this->created(
            new ScheduleResource(
                $this->service->store(
                    $request->validated()
                )
            )
        );
    }

    public function update(UpdateScheduleRequest $request, int $schedule)
    {
        $this->authorizeAbility($request, 'employee.schedules.update');

        return $this->resource(
            new ScheduleResource(
                $this->service->update(
                    $schedule,
                    $request->validated()
                )
            ),
            'Schedule updated successfully.'
        );
    }

    public function destroy(Request $request, int $schedule)
    {
        $this->authorizeAbility($request, 'employee.schedules.delete');

        $this->service->delete($schedule);

        return $this->deleted(
            'Schedule deleted successfully.'
        );
    }
}