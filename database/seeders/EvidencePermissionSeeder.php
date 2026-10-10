<?php

namespace Database\Seeders;

use App\Domains\System\Models\Permission;
use App\Domains\System\Models\Role;
use Illuminate\Database\Seeder;

class EvidencePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['resource' => 'reconciliations', 'action' => 'view'],
            ['resource' => 'reconciliations', 'action' => 'create'],
            ['resource' => 'supplier_observations', 'action' => 'view'],
            ['resource' => 'supplier_observations', 'action' => 'create'],
            ['resource' => 'market_observations', 'action' => 'view'],
            ['resource' => 'market_observations', 'action' => 'create'],
            ['resource' => 'surveys', 'action' => 'view'],
            ['resource' => 'surveys', 'action' => 'create'],
            ['resource' => 'surveys', 'action' => 'update'],
            ['resource' => 'surveys', 'action' => 'publish'],
            ['resource' => 'responses', 'action' => 'view'],
            ['resource' => 'responses', 'action' => 'create'],
            ['resource' => 'products', 'action' => 'view'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                [
                    'module' => 'evidence',
                    'resource' => $permission['resource'],
                    'action' => $permission['action'],
                ],
                [
                    'code' => "evidence.{$permission['resource']}.{$permission['action']}",
                    'description' => ucfirst($permission['action']).' '.str_replace('_', ' ', $permission['resource']),
                    'is_active' => true,
                ]
            );
        }

        $admin = Role::query()->where('code', 'admin')->first();

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(
                Permission::query()
                    ->where('module', 'evidence')
                    ->pluck('id')
                    ->all()
            );
        }
    }
}
