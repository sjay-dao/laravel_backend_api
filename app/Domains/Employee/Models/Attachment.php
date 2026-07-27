<?php

namespace App\Domains\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;
    protected $table = 'employee_attachments';
    protected $fillable = ['employee_id', 'category', 'original_name', 'path', 'disk', 'mime_type', 'size', 'uploaded_by'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
