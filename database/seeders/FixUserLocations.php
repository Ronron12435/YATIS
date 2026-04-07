<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class FixUserLocations extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sagay City boundaries
        $sagayBounds = [
            'minLat' => 10.8500,
            'maxLat' => 10.9400,
            'minLng' => 123.3800,
            'maxLng' => 123.4700
        ];

        // Find all users with incorrect locations (outside Sagay City)
        $users = User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($users as $user) {
            $isInSagay = $user->latitude >= $sagayBounds['minLat'] &&
                        $user->latitude <= $sagayBounds['maxLat'] &&
                        $user->longitude >= $sagayBounds['minLng'] &&
                        $user->longitude <= $sagayBounds['maxLng'];

            if (!$isInSagay) {
                echo "User {$user->id} ({$user->first_name} {$user->last_name}) is outside Sagay City\n";
                echo "  Current: Lat {$user->latitude}, Lng {$user->longitude}\n";
                // Set to Sagay City center as default
                $user->update([
                    'latitude' => 10.8967,
                    'longitude' => 123.4253
                ]);
                echo "  Updated to Sagay City center\n";
            }
        }

        echo "Location fix complete!\n";
    }
}
