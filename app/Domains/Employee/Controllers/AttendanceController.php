<?php

namespace App\Domains\Employee\Controllers;

use App\Domains\Employee\Requests\StoreAttendanceRequest;
use App\Domains\Employee\Requests\UpdateAttendanceRequest;
use App\Domains\Employee\Requests\BulkAttendanceRequest;
use App\Domains\Employee\Resources\AttendanceResource;
use App\Domains\Employee\Resources\AttendanceCalendarResource;
use App\Domains\Employee\Services\AttendanceService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class AttendanceController extends BaseApiController
{
    public function __construct(protected AttendanceService $service) {}

    public function calendar(Request $request)
    {
        $this->authorizeAbility($request, 'employee.attendance.view');

        return $this->resource(
            new AttendanceCalendarResource(
                $this->service->calendar(
                    (int) $request->employee_id,
                    (int) $request->year,
                    (int) $request->month
                )
            ),
            'Attendance calendar retrieved successfully.'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'employee.attendance.view');

        return $this->success(
            AttendanceResource::collection(
                $this->service->index($request->all())
            ),
            'Attendance retrieved successfully.'
        );
    }

    public function show(Request $request, int $attendance)
    {
        $this->authorizeAbility($request, 'employee.attendance.view');

        return $this->resource(
            new AttendanceResource(
                $this->service->find($attendance)
            ),
            'Attendance retrieved successfully.'
        );
    }

    public function store(StoreAttendanceRequest $request)
    {
        $this->authorizeAbility($request, 'employee.attendance.create');

        return $this->created(
            new AttendanceResource(
                $this->service->store(
                    $request->validated()
                )
            ),
            'Attendance created successfully.'
        );
    }

    public function update(UpdateAttendanceRequest $request, int $attendance)
    {
        $this->authorizeAbility($request, 'employee.attendance.update');

        return $this->resource(
            new AttendanceResource(
                $this->service->update(
                    $attendance,
                    $request->validated()
                )
            ),
            'Attendance updated successfully.'
        );
    }

    public function destroy(Request $request, int $attendance)
    {
        $this->authorizeAbility($request, 'employee.attendance.delete');

        $this->service->delete($attendance);

        return $this->deleted(
            'Attendance deleted successfully.'
        );
    }

    public function bulk(BulkAttendanceRequest $request)
    {
        $this->authorizeAbility($request, 'employee.attendance.create');

        $this->service->bulkSave(
            $request->validated()
        );

        return $this->success(
            null,
            'Attendance saved successfully.'
        );
    }
}