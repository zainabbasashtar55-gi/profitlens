<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage-team',
            'invite-users',
            'export-reports',
            'edit-settings',
            'view-reports',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $owner  = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $admin  = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        $owner->syncPermissions($permissions);

        $admin->syncPermissions([
            'invite-users',
            'export-reports',
            'edit-settings',
            'view-reports',
        ]);

        $member->syncPermissions([
            'view-reports',
        ]);

        $defaultCategories = [
            ['name' => 'Rent',      'color' => '#ef4444'],
            ['name' => 'Payroll',   'color' => '#f59e0b'],
            ['name' => 'Software',  'color' => '#6366f1'],
            ['name' => 'Marketing', 'color' => '#ec4899'],
            ['name' => 'Travel',    'color' => '#10b981'],
            ['name' => 'Utilities', 'color' => '#0ea5e9'],
            ['name' => 'Office',    'color' => '#8b5cf6'],
        ];

        foreach ($defaultCategories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat['name']], ['color' => $cat['color']]);
        }
    }
}
