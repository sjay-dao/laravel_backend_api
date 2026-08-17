<?php

namespace App\Domains\Employee\Models;

use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTransaction extends Model
{
    use SoftDeletes;
    protected $table = 'employee_transactions';
    protected $fillable = ['transaction_no', 'employee_id', 'type', 'transaction_date', 'amount', 'ledger_effect', 'currency', 'reference_type', 'reference_no', 'remarks', 'status', 'reverses_transaction_id', 'created_by', 'voided_by', 'voided_at'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:2', 'ledger_effect' => 'decimal:2', 'voided_at' => 'datetime'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function reversalOf() { return $this->belongsTo(self::class, 'reverses_transaction_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
