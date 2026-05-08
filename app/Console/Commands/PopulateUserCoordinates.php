<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PopulateUserCoordinates extends Command
{
    protected $signature = 'users:populate-coordinates';
    protected $description = 'Populate coordinates for all users in Sagay City';

    public function handle()
    {
        $defaultLat = 10.8967;
        $defaultLng = 123.4253;

        $users = User::where('role', 'user')->get();

        if ($users->isEmpty()) {
            $this->info('No users found with role "user"');
            return;
        }

        $this->info("Found {$users->count()} users. Populating coordinates...");

        $spreadIndex = 0;
        foreach ($users as $user) {
            // Spread users in a grid pattern around Sagay City
            $latOffset = ($spreadIndex % 3) * 0.003;  // 3 rows
            $lngOffset = floor($spreadIndex / 3) * 0.003;  // 3 columns
            $lat = $defaultLat + $latOffset;
            $lng = $defaultLng + $lngOffset;

            $user->update([
                'latitude' => $lat,
                'longitude' => $lng,
                'location_updated_at' => now(),
                'online_status' => 'online',
            ]);

            $this->line("✓ {$user->username} → lat={$lat}, lng={$lng}");
            $spreadIndex++;
        }

        $this->info("\n✓ All user coordinates populated successfully!");
    }
}
