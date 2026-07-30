<?php
namespace App\Domains\Payroll\Repositories;

use App\Domains\Payroll\Models\PayrollDetail;

class PayrollDetailRepository
{
    public function create(array $data)
    {
        return PayrollDetail::create($data);
    }

    public function deleteByRun(int $runId)
    {
        PayrollDetail::where('payroll_run_id', $runId)
            ->delete();
    }

    public function listByRun(int $runId)
    {
        return PayrollDetail::query()
            ->with([
                'employee',
                'salaryContract'
            ])
            ->where('payroll_run_id', $runId)
            ->orderBy('employee_id')
            ->get();
    }
}