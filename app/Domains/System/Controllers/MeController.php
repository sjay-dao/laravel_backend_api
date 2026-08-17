<?php

namespace App\Domains\System\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load([
            'roles.permissions'
        ]);

        $permissions = $user->roles
            ->flatMap(function ($role) {
                return $role->permissions;
            })
            ->unique('id')
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],

            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ];
            })->values(),

            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'module' => $permission->module,
                    'resource' => $permission->resource,
                    'action' => $permission->action,
                    'code' => $permission->code,
                ];
            })->values(),
        ]);
    }
}