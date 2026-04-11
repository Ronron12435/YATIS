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
        $this->info('⚠️  This command has been disabled to prevent recreating deleted friendships.');
        $this->info('Friendships are now managed through the UI only.');
        return 0;
    }
}
