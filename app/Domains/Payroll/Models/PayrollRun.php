<?php
namespace App\Domains\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Payroll\Models\PayrollDetail;

class PayrollRun extends Model
{
    protected $fillable = [
        'payroll_no',
        'period_from',
        'period_to',
        'pay_date',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'pay_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }
}