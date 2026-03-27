<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Friendship;
use Illuminate\Console\Command;

class CreateTestFriendships extends Command
{
    protected $signature = 'friendships:create-test';
    protected $description = 'Create test friendships between first 5 users';

    public function handle()
    {
        $users = User::limit(5)->get();
        
        if ($users->count() < 2) {
            $this->error('Not enough users to create friendships');
            return 1;
        }

        $count = 0;
        for ($i = 0; $i < $users->count() - 1; $i++) {
            for ($j = $i + 1; $j < $users->count(); $j++) {
                Friendship::firstOrCreate(
                    ['user_id' => $users[$i]->id, 'friend_id' => $users[$j]->id],
                    ['status' => 'accepted']
                );
                $count++;
            }
        }

        $this->info("✓ Created $count test friendships");
        
        $total = Friendship::where('status', 'accepted')->count();
        $this->info("Total accepted friendships: $total");
        
        return 0;
    }
}
