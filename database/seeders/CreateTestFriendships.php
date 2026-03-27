<?php

namespace Database\Seeders;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreateTestFriendships extends Seeder
{
    public function run(): void
    {
        $users = User::limit(5)->get();
        
        if ($users->count() < 2) {
            $this->command->info('Not enough users to create friendships');
            return;
        }

        // Create friendships between first 5 users
        for ($i = 0; $i < $users->count() - 1; $i++) {
            for ($j = $i + 1; $j < $users->count(); $j++) {
                Friendship::firstOrCreate(
                    [
                        'user_id' => $users[$i]->id,
                        'friend_id' => $users[$j]->id,
                    ],
                    ['status' => 'accepted']
                );
            }
        }

        $this->command->info('Test friendships created successfully');
    }
}
