<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AssignUserCoordinates extends Seeder
{
    public function run(): void
    {
        // Sagay City center coordinates
        $baseLat = 10.8967;
        $baseLng = 123.4253;
        
        // Get all users with role='user'
        $users = User::where('role', 'user')->get();
        
        foreach ($users as $index => $user) {
            // Spread users in a small radius around Sagay City center
            $offset = $index * 0.002; // ~200 meters per user
            $angle = ($index * 45) * (M_PI / 180); // Spread in 45-degree increments
            
            $lat = $baseLat + ($offset * cos($angle));
            $lng = $baseLng + ($offset * sin($angle));
            
            $user->update([
                'latitude' => $lat,
                'longitude' => $lng,
                'location_name' => 'Sagay City',
                'location_updated_at' => now(),
            ]);
            
            echo "✓ Updated {$user->username} with coordinates ({$lat}, {$lng})\n";
        }
        
        echo "\n✅ All users assigned coordinates!\n";
    }
}
