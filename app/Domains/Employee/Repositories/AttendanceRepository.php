<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\AttendanceRecord;

class AttendanceRepository
{

    public function index(array $filters = [])
    {
        $query = AttendanceRecord::query();

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('attendance_date', $filters['year']);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('attendance_date', $filters['month']);
        }

        return $query
            ->latest('attendance_date')
            ->paginate(20);
    }

    public function find(int $id): AttendanceRecord
    {
        return AttendanceRecord::findOrFail($id);
    }

    public function calendar(int $employeeId, int $year, int $month)
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->get();
    }

    public function store(array $data): AttendanceRecord
    {
        return AttendanceRecord::create($data);
    }

    public function update(int $id, array $data): AttendanceRecord
    {
        $attendance = $this->find($id);

        $attendance->update($data);

        return $attendance->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }

    public function summary(int $employeeId, int $year, int $month)
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }
}