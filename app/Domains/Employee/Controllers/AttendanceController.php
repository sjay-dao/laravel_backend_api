<?php

namespace App\Domains\Employee\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Domains\Employee\Requests\StoreAttendanceRequest;
use App\Domains\Employee\Requests\UpdateAttendanceRequest;

use App\Domains\Employee\Services\AttendanceService;

use App\Domains\Employee\Resources\AttendanceResource;
use App\Domains\Employee\Resources\AttendanceCalendarResource;
use App\Domains\Employee\Requests\BulkAttendanceRequest;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $service
    ) {
    }

    /**
     * Monthly calendar
     */
    public function calendar(Request $request)
    {
        $records = $this->service->calendar(
            (int) $request->employee_id,
            (int) $request->year,
            (int) $request->month
        );

        return new AttendanceCalendarResource($records);
    }

    /**
     * View a single attendance record
     */
    public function show(int $attendance)
    {
        $record = $this->service->find($attendance);

        return new AttendanceResource($record);
    }

    /**
     * Create attendance
     */
    public function store(StoreAttendanceRequest $request)
    {
        $record = $this->service->store(
            $request->validated()
        );

        return new AttendanceResource($record);
    }

    /**
     * Update attendance
     */
    public function update(
        UpdateAttendanceRequest $request,
        int $attendance
    ) {
        $record = $this->service->update(
            $attendance,
            $request->validated()
        );

        return new AttendanceResource($record);
    }

    /**
     * Delete attendance
     */
    public function destroy(int $attendance)
    {
        $this->service->delete($attendance);

        return response()->json([
            'message' => 'Attendance deleted successfully.'
        ]);
    }

    public function index(Request $request)
    {
        return AttendanceResource::collection(
            $this->service->index($request->all())
        );
    }

    public function bulk(
        BulkAttendanceRequest $request
    ) {
        $this->service->bulkSave(
            $request->validated()
        );

        return response()->json([
            'success' => true,
        ]);
    }
}