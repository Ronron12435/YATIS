<?php

namespace Database\Seeders;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreateTestFriendships extends Seeder
{
    public function run(): void
    {
        $this->command->info('⚠️  Test friendships seeder has been disabled to prevent recreating deleted friendships.');
        $this->command->info('Friendships are now managed through the UI only.');
    }
}
