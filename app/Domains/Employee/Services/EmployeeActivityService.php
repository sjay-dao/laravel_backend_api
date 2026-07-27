<?php

namespace App\Domains\Employee\Services;

use App\Domains\Employee\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EmployeeActivityService
{
    public function record(string $action, Model $subject, ?int $employeeId = null, array $old = [], array $new = []): void
    {
        ActivityLog::create(['employee_id' => $employeeId, 'subject_type' => $subject::class, 'subject_id' => $subject->getKey(), 'action' => $action, 'old_values' => $old ?: null, 'new_values' => $new ?: null, 'actor_id' => Auth::id(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }
}
