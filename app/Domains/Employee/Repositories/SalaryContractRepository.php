<?php

namespace App\Domains\Employee\Repositories;

use App\Domains\Employee\Models\SalaryContract;

class SalaryContractRepository
{
    public function index(array $filters = [])
    {
        $query = SalaryContract::query()
            ->with('employee');

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['pay_basis'])) {
            $query->where('pay_basis', $filters['pay_basis']);
        }

        return $query
            ->latest('effective_from')
            ->paginate(20);
    }

    public function find(int $id): SalaryContract
    {
        return SalaryContract::with('employee')
            ->findOrFail($id);
    }

    public function store(array $data): SalaryContract
    {
        // Only one active contract per employee
        if (!empty($data['is_active'])) {
            SalaryContract::where('employee_id', $data['employee_id'])
                ->update([
                    'is_active' => false
                ]);
        }

        return SalaryContract::create($data);
    }

    public function update(int $id, array $data): SalaryContract
    {
        $contract = $this->find($id);

        // If activating this contract,
        // deactivate every other contract.
        if (!empty($data['is_active'])) {

            SalaryContract::where('employee_id', $contract->employee_id)
                ->where('id', '<>', $contract->id)
                ->update([
                    'is_active' => false
                ]);

        }

        $contract->update($data);

        return $contract->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activeContract(int $employeeId): ?SalaryContract
    {
        return SalaryContract::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();
    }
}