<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EnsureAdminUser extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin user exists
        $admin = User::where('email', 'admin@yatis.local')->first();

        if (!$admin) {
            User::create([
                'username' => 'admin',
                'email' => 'admin@yatis.local',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_premium' => false,
                'is_private' => false,
            ]);
            $this->command->info('✓ Admin user created!');
        } else {
            // Ensure admin has correct role
            if ($admin->role !== 'admin') {
                $admin->update(['role' => 'admin']);
                $this->command->info('✓ Admin user role updated!');
            } else {
                $this->command->info('✓ Admin user already exists with correct role');
            }
        }

        // Also ensure user 4 is admin (if it exists)
        $user4 = User::find(4);
        if ($user4 && $user4->role !== 'admin') {
            $user4->update(['role' => 'admin']);
            $this->command->info('✓ User 4 updated to admin role!');
        }
    }
}
