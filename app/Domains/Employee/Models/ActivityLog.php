<?php

namespace App\Domains\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'employee_activity_logs';
    protected $fillable = ['employee_id', 'subject_type', 'subject_id', 'action', 'old_values', 'new_values', 'actor_id', 'ip_address', 'user_agent'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
