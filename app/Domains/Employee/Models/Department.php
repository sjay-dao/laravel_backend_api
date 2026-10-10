<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $table = 'employee_departments';
    protected $fillable = ['code', 'name', 'parent_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function positions() { return $this->hasMany(Position::class); }
    public function employees() { return $this->hasMany(Employee::class); }
}
