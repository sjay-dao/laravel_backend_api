<?php

namespace App\Domains\Employee\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Domains\Employee\Services\ScheduleService;

use App\Domains\Employee\Requests\StoreScheduleRequest;
use App\Domains\Employee\Requests\UpdateScheduleRequest;

use App\Domains\Employee\Resources\ScheduleResource;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $service
    ) {
    }

    public function index(Request $request)
    {
        return ScheduleResource::collection(
            $this->service->index($request->all())
        );
    }

    public function show(int $schedule)
    {
        return new ScheduleResource(
            $this->service->find($schedule)
        );
    }

    public function store(StoreScheduleRequest $request)
    {
        return new ScheduleResource(
            $this->service->store(
                $request->validated()
            )
        );
    }

    public function update(
        UpdateScheduleRequest $request,
        int $schedule
    )
    {
        return new ScheduleResource(
            $this->service->update(
                $schedule,
                $request->validated()
            )
        );
    }

    public function destroy(int $schedule)
    {
        $this->service->delete($schedule);

        return response()->json([
            'message' => 'Schedule deleted successfully.'
        ]);
    }
}