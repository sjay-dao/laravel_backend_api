<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\SalaryContract;
use App\Domains\Employee\Models\AttendanceRecord;
use App\Domains\Employee\Models\EmployeeTransaction;

class PayrollComputationService
{
    public function compute(
        int $employeeId,
        int $year,
        int $month
    ) {

        $employee = Employee::findOrFail($employeeId);

        $contract = SalaryContract::query()
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        $attendance = AttendanceRecord::query()
            ->where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $transactions = EmployeeTransaction::query()
            ->where('employee_id', $employeeId)
            ->get();

        
       $attendanceSummary =
            $this->summarizeAttendance($attendance);

        $transactionSummary =
            $this->summarizeTransactions($transactions);

        $result = match ($contract->pay_basis) {

            'DAILY' => $this->computeDaily(
                $contract,
                $attendanceSummary
            ),

            default => [
                'gross_salary' => 0,
            ],
        };

        $netSalary =
            $result['gross_salary']
            - $transactionSummary['advances']
            - $transactionSummary['deductions'];

        return [

            'employee_id' => $employee->id,

            'salary_contract_id' => $contract->id,

            'present_days' => $attendanceSummary['present'],

            'absent_days' => $attendanceSummary['absent'],

            'leave_days' => $attendanceSummary['leave'],

            'half_days' => $attendanceSummary['half_day'],

            'worked_minutes' => $attendance
                ->sum('worked_minutes'),

            'basic_pay' => $result['gross_salary'],

            'allowances' => 0,

            'overtime_pay' => 0,

            'gross_pay' => $result['gross_salary'],

            'deductions' =>
                $transactionSummary['advances']
                + $transactionSummary['deductions'],

            'net_pay' => round($netSalary, 2),

        ];
    }

    protected function summarizeAttendance($attendance): array
    {
        return [
            'present' => $attendance->where('status', 'PRESENT')->count(),
            'half_day' => $attendance->where('status', 'HALF_DAY')->count(),
            'absent' => $attendance->where('status', 'ABSENT')->count(),
            'leave' => $attendance->where('status', 'LEAVE')->count(),
            'holiday' => $attendance->where('status', 'HOLIDAY')->count(),
            'rest_day' => $attendance->where('status', 'REST_DAY')->count(),
        ];
    }

    protected function computeDaily(
        $contract,
        array $attendance
    ): array {

        $gross =
            ($attendance['present'] * $contract->salary_rate)
            + ($attendance['half_day'] * ($contract->salary_rate / 2));

        return [
            'gross_salary' => round($gross, 2),
        ];
    }

    protected function summarizeTransactions($transactions): array
    {
        return [

            'advances' => $transactions
                ->filter(fn ($t) => strtoupper($t->type) === 'ADVANCE')
                ->sum('amount'),

            'deductions' => $transactions
                ->filter(fn ($t) => strtoupper($t->type) === 'DEDUCTION')
                ->sum('amount'),

        ];
    }
}