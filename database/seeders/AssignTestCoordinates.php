<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AssignTestCoordinates extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Different locations across Sagay City for testing
        $locations = [
            ['lat' => 10.8961, 'lng' => 123.4155, 'name' => 'Tinampaan'],
            ['lat' => 10.8950, 'lng' => 123.4180, 'name' => 'Tinampaan South'],
            ['lat' => 10.8967, 'lng' => 123.4253, 'name' => 'Downtown Sagay'],
            ['lat' => 10.8980, 'lng' => 123.4270, 'name' => 'Downtown North'],
            ['lat' => 10.8920, 'lng' => 123.4200, 'name' => 'Calinog'],
            ['lat' => 10.8905, 'lng' => 123.4220, 'name' => 'Calinog East'],
            ['lat' => 10.9000, 'lng' => 123.4150, 'name' => 'Jaro'],
            ['lat' => 10.9020, 'lng' => 123.4170, 'name' => 'Jaro North'],
            ['lat' => 10.8880, 'lng' => 123.4300, 'name' => 'Tangkaan'],
            ['lat' => 10.8870, 'lng' => 123.4280, 'name' => 'Tangkaan West'],
        ];

        // Get all normal users (role='user')
        $users = User::where('role', 'user')->get();

        echo "\n📍 Assigning test coordinates to " . $users->count() . " users...\n";

        foreach ($users as $index => $user) {
            $location = $locations[$index % count($locations)];

            $user->update([
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'location_name' => $location['name'],
                'location_updated_at' => now(),
                'online_status' => 'online',  // Set to online for testing
            ]);

            echo "✅ {$user->username} → {$location['name']} ({$location['lat']}, {$location['lng']})\n";
        }

        echo "\n✨ Test coordinates assigned! Users will now appear on the map.\n";
        echo "📌 Note: These are test coordinates. Real coordinates will be captured via GPS when users log in.\n\n";
    }
}
