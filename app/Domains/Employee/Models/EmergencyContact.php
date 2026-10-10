<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    protected $table = 'employee_emergency_contacts';
    protected $fillable = ['employee_id', 'name', 'relationship', 'mobile_number', 'phone_number', 'address', 'is_primary'];
    protected $casts = ['is_primary' => 'boolean'];
    public function employee() { return $this->belongsTo(Employee::class); }
}
