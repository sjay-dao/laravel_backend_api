<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'module',
        'resource',
        'action',
        'code',
        'description',
        'is_active',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions'
        );
    }
}