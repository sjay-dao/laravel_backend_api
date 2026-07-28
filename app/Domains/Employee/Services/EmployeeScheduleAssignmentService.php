<?php

namespace App\Domains\Employee\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Domains\Employee\Models\EmployeeScheduleAssignment;
use App\Domains\Employee\Repositories\EmployeeScheduleAssignmentRepository;
use App\Models\User;

class EmployeeScheduleAssignmentService
{
    public function __construct(
        protected EmployeeScheduleAssignmentRepository $repository
    ) {
    }

    public function paginate()
    {
        return $this->repository->paginate();
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

   public function create(
        array $data,
        User $user
    )
    {
        return DB::transaction(function () use ($data, $user) {

            $current = $this->repository
                ->activeAssignment($data['employee_id']);

            if ($current) {

                $current->update([
                    'effective_to' => Carbon::parse(
                        $data['effective_from']
                    )->subDay(),

                    'is_active' => false
                ]);

            }

            $data['is_active'] = true;

            $data['assigned_by'] = $user->id;

            return $this->repository->create($data);

        });
    }

    public function update(int $id, array $data)
    {
        $assignment = $this->repository->find($id);

        return $this->repository
            ->update($assignment, $data);
    }

    public function delete(int $id)
    {
        $assignment = $this->repository->find($id);

        $this->repository->delete($assignment);
    }
}