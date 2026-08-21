<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'master.create', 'master.read', 'master.update', 'master.delete',
            'user.manage',
            'test.create', 'test.read',
            'report.read', 'report.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminMaster = Role::firstOrCreate(['name' => 'admin_master']);
        $adminMaster->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(['master.read', 'test.read', 'report.read', 'report.export']);

        $inspector = Role::firstOrCreate(['name' => 'inspector']);
        $inspector->syncPermissions(['test.create', 'test.read']);
    }
}
