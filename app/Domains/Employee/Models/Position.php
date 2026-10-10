<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;

    protected $table = 'employee_positions';
    protected $fillable = ['code', 'name', 'department_id', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function department() { return $this->belongsTo(Department::class); }
    public function employees() { return $this->hasMany(Employee::class); }
}
