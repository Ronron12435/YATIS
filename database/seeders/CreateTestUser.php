<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test admin user
        User::updateOrCreate(
            ['email' => 'test@yatis.com'],
            [
                'username' => 'testadmin',
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => 'test@yatis.com',
                'password' => Hash::make('test123'),
                'role' => 'admin'
            ]
        );

        echo "✓ Test user created!\n";
        echo "Email: test@yatis.com\n";
        echo "Password: test123\n";
    }
}
