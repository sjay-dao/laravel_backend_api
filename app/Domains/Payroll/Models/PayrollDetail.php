<?php
namespace App\Domains\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Employee\Models\Employee;
use App\Domains\Employee\Models\SalaryContract;
class PayrollDetail extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'salary_contract_id',

        'present_days',
        'absent_days',
        'leave_days',
        'half_days',
        'worked_minutes',

        'basic_pay',
        'allowances',
        'overtime_pay',
        'gross_pay',
        'deductions',
        'net_pay',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function salaryContract()
    {
        return $this->belongsTo(SalaryContract::class);
    }
}