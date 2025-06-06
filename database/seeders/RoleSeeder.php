<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $guestRole = Role::firstOrCreate(['name' => 'guest']);

        // Get all permissions
        $allPermissions = Permission::all();

        // Admin gets all permissions
        $adminRole->syncPermissions($allPermissions);

        // Editor gets limited permissions (can manage users but not roles)
        $editorPermissions = Permission::where('name', 'like', '%user%')
            ->orWhere('name', 'like', '%page%')
            ->orWhere('name', 'like', '%widget%')
            ->get();
        $editorRole->syncPermissions($editorPermissions);

        // Guest gets only view permissions
        $guestPermissions = Permission::where('name', 'like', 'view%')
            ->where('name', 'not like', '%delete%')
            ->where('name', 'not like', '%create%')
            ->where('name', 'not like', '%update%')
            ->get();
        $guestRole->syncPermissions($guestPermissions);

        $this->command->info('Default roles created successfully!');
        $this->command->info('- Admin: Full access to all resources');
        $this->command->info('- Editor: Can manage users and view roles');
        $this->command->info('- Guest: View-only access');
    }
}
