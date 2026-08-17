<?php

namespace App\Domains\System\Models;

use Faker\Provider\bn_BD\Address;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

use App\Domains\System\Models\Role;
use App\Domains\System\Models\Permission;
use App\Domains\System\Services\AuthorizationService;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)
            ->where('is_default', true);
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles'
        );
    }

    public function permissions()
    {
        return Permission::whereHas('roles.users', function ($query) {
            $query->where('users.id', $this->id);
        })->get();
    }

    public function hasRole(string $role): bool
    {
        return app(AuthorizationService::class)
            ->hasRole($this, $role);
    }

    public function hasPermission(string $permission): bool
    {
        return app(AuthorizationService::class)
            ->hasPermission($this, $permission);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}