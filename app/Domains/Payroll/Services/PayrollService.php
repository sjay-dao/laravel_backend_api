<?php
namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Repositories\PayrollRunRepository;
use App\Domains\Payroll\Repositories\PayrollDetailRepository;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\AttendanceRecord;
use App\Domains\Employee\Models\SalaryContract;
use App\Domains\Payroll\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

use App\Domains\Payroll\Services\PayrollComputationService;
class PayrollService
{
    public function __construct(
        protected PayrollRunRepository $runs,
        protected PayrollDetailRepository $details,
        protected PayrollComputationService $computation,
    ) {
    }

    public function index()
    {
        return $this->runs->paginate();
    }
    
    public function preview(array $data)
    {
        return $this->computation->compute(
            $data['employee_id'],
            $data['year'],
            $data['month']
        );
    }

    public function show(int $id)
    {
        return $this->runs->find($id);
    }

    public function create(array $data)
    {
        return $this->runs->create($data);
    }

    public function update($run, array $data)
    {
        return $this->runs->update($run, $data);
    }

    public function delete($run)
    {
        return $this->runs->delete($run);
    }

    public function compute(int $runId)
    {
        return DB::transaction(function () use ($runId) {

            $run = PayrollRun::findOrFail($runId);

            // Delete previous computation
            $this->details->deleteByRun($run->id);

            // Generate new payroll details
            $this->generatePayrollDetails($run);

            // Update payroll status
            $run->update([
                'status' => 'COMPUTED',
            ]);

            return $run->fresh();

        });
    }

    protected function generatePayrollDetails(PayrollRun $run): void
    {
        $employees = Employee::query()
            ->whereHas('salaryContracts', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        foreach ($employees as $employee) {

            $result = $this->computation->compute(
                $employee->id,
                $run->period_from->year,
                $run->period_from->month
            );

            $this->details->create([

                'payroll_run_id' => $run->id,

                'employee_id' => $result['employee_id'],

                'salary_contract_id' => $result['salary_contract_id'],

                'present_days' => $result['present_days'],

                'absent_days' => $result['absent_days'],

                'leave_days' => $result['leave_days'],

                'half_days' => $result['half_days'],

                'worked_minutes' => $result['worked_minutes'],

                'basic_pay' => $result['basic_pay'],

                'allowances' => $result['allowances'],

                'overtime_pay' => $result['overtime_pay'],

                'gross_pay' => $result['gross_pay'],

                'deductions' => $result['deductions'],

                'net_pay' => $result['net_pay'],

            ]);
        }
    }

    public function details(int $runId)
    {
        return $this->details->listByRun($runId);
    }

    public function cancel(PayrollRun $run)
    {
        if ($run->status === 'POSTED') {
            abort(422, 'Posted payrolls cannot be cancelled.');
        }

        $run->update([
            'status' => 'CANCELLED',
        ]);

        return $run->fresh();
    }
}