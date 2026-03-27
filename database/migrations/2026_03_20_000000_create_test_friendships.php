<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Friendship;

return new class extends Migration
{
    public function up(): void
    {
        // Get first 5 users
        $users = User::limit(5)->get();
        
        if ($users->count() >= 2) {
            // Create friendships between them
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
        }
    }

    public function down(): void
    {
        // No rollback needed for test data
    }
};
