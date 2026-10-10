<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryContract extends Model
{
    protected $fillable = [

        'employee_id',

        'pay_basis',

        'salary_rate',

        'effective_from',

        'effective_to',

        'is_active',

        'remarks',

        'created_by',

        'updated_by',
    ];

    protected $casts = [

        'effective_from' => 'date',

        'effective_to' => 'date',

        'is_active' => 'boolean',

    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}