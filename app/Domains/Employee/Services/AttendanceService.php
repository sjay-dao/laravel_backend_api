<?php

namespace App\Domains\Employee\Services;

use App\Domains\Employee\Repositories\AttendanceRepository;

class AttendanceService
{
    public function __construct(
        protected AttendanceRepository $repository
    ) {
    }

    public function index(array $filters = [])
    {
        return $this->repository->index($filters);
    }

    public function calendar(
        int $employeeId,
        int $year,
        int $month
    ) {
        return $this->repository->calendar(
            $employeeId,
            $year,
            $month
        );
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function store(array $data)
    {
        return $this->repository->store($data);
    }

    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }

    public function summary(
        int $employeeId,
        int $year,
        int $month
    ) {
        return $this->repository->summary(
            $employeeId,
            $year,
            $month
        );
    }

    public function bulkSave(array $data): void
    {
        foreach ($data['dates'] as $date) {

            $this->repository->upsert(
                $data['employee_id'],
                $date,
                $data['status'],
                'Manual Entry'
            );
        }
    }
}