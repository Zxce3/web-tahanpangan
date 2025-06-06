<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultUserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default users if they don't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $editorUser = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $guestUser = User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $editorRole = Role::where('name', 'editor')->first();
        $guestRole = Role::where('name', 'guest')->first();

        // Assign roles to users
        if ($adminRole && !$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
            $this->command->info('Admin role assigned to admin@example.com');
        }

        if ($editorRole && !$editorUser->hasRole('editor')) {
            $editorUser->assignRole($editorRole);
            $this->command->info('Editor role assigned to editor@example.com');
        }

        if ($guestRole && !$guestUser->hasRole('guest')) {
            $guestUser->assignRole($guestRole);
            $this->command->info('Guest role assigned to guest@example.com');
        }

        $this->command->info('Default users with roles created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('- admin@example.com / password (Admin access)');
        $this->command->info('- editor@example.com / password (Editor access)');
        $this->command->info('- guest@example.com / password (Guest access)');
    }
}
