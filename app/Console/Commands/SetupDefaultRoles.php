<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SetupDefaultRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup default roles and users for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up default roles and users...');

        // Create default users
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin'
            ],
            [
                'name' => 'Editor User',
                'email' => 'editor@example.com',
                'role' => 'editor'
            ],
            [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
                'role' => 'guest'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role if it exists
            $role = Role::where('name', $userData['role'])->first();
            if ($role && !$user->hasRole($userData['role'])) {
                $user->assignRole($role);
                $this->info("✓ {$userData['role']} role assigned to {$userData['email']}");
            } else {
                $this->info("- {$userData['email']} already has {$userData['role']} role");
            }
        }

        $this->info('');
        $this->info('✓ Default setup completed!');
        $this->info('');
        $this->info('Login credentials:');
        $this->info('- admin@example.com / password (Full admin access)');
        $this->info('- editor@example.com / password (Limited editor access)');
        $this->info('- guest@example.com / password (View-only access)');

        return 0;
    }
}
