<?php

namespace App\Domains\Finance\Models;

use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_date', 'category', 'description', 'amount_cents',
        'reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount_cents' => 'integer',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
