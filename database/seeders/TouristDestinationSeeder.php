<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TouristDestination;

class TouristDestinationSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TouristDestination::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Coordinates extracted directly from Google Maps URLs provided by user
        $destinations = [
            ['name' => 'Balay Kauswagan',
             'category' => 'Cultural & Historical',
             'latitude' => 10.8775549, 'longitude' => 123.409311,
             'location' => 'Sagay City Proper',
             'description' => 'A multi-purpose venue offering seminars, skills training, art exhibits, trade fairs, and wedding receptions. Also provides accommodations for tourists.'],

            ['name' => 'Carbin Reef',
             'category' => 'Marine & Island',
             'latitude' => 10.9781379, 'longitude' => 123.4648056,
             'location' => 'Sagay Marine Reserve',
             'description' => 'A tongue-shaped sandbar and marine sanctuary part of the Sagay Marine Reserve. Features crystal-clear turquoise waters, vibrant coral reefs, and diverse marine life including parrotfish, angelfish, and sea turtles. Perfect for snorkeling and swimming. Access via boat from Old Sagay Wharf.'],

            ['name' => 'Himoga-an River Cruise',
             'category' => 'Eco-Tourism & Nature',
             'latitude' => 10.867929, 'longitude' => 123.3462192,
             'location' => 'Brgy. Fabrica',
             'description' => 'A two-hour scenic boat journey along Sagay\'s longest river. Starts at Brgy. Fabrica Wharf and ends in Brgy. Old Sagay. Features lush mangrove forests and migratory bird sightings.'],

            ['name' => 'Lady Circle / Sagay City Garden',
             'category' => 'Parks & Public Spaces',
             'latitude' => 10.8777721, 'longitude' => 123.4129234,
             'location' => 'Sagay City Proper',
             'description' => 'A 2.8-acre park suitable for walking and relaxation. Part of the "Living Tree Museum" concept.'],

            ['name' => 'Sagay City Public Plaza',
             'category' => 'Parks & Public Spaces',
             'latitude' => 10.8961448, 'longitude' => 123.4153952,
             'location' => 'Sagay City Proper',
             'description' => 'The city\'s central public plaza. Features the Legendary Siete steam train on display. Venue for city events and the Sinigayan Festival.'],

            ['name' => 'Mangrove Park and Beach Resort',
             'category' => 'Eco-Tourism & Nature',
             'latitude' => 10.948697, 'longitude' => 123.4180203,
             'location' => 'Brgy. Old Sagay',
             'description' => 'A scenic mangrove park and beach resort along the coast of Old Sagay. Features mangrove boardwalks, beach access, and eco-tourism activities.'],

            ['name' => 'Margaha Beach',
             'category' => 'Art & Community',
             'latitude' => 10.9491526, 'longitude' => 123.4209645,
             'location' => 'Brgy. Old Sagay',
             'description' => 'Known for its black sand which locals claim has therapeutic properties. Features seven poles (haligi) representing the seven disciplines of art. Home to artist Nunelucio Alvarado\'s gallery. Visitors can enjoy sunrise/sunset views with coffee at Kape Albarako.'],

            ['name' => 'Museo sang Bata sa Negros',
             'category' => 'Cultural & Historical',
             'latitude' => 10.947863, 'longitude' => 123.426213,
             'location' => 'Brgy. Old Sagay',
             'description' => 'The first hands-on, interactive museum outside Metro Manila. Shaped like a whale, located on the shoreline of Barangay Old Sagay. Features exhibits on marine conservation and a Junior Guide Program training children aged 8-12 as tour guides.'],

            ['name' => 'Kape Albarako',
             'category' => 'Food & Dining',
             'latitude' => 10.9491032, 'longitude' => 123.421129,
             'location' => 'Margaha Beach, Brgy. Old Sagay',
             'description' => 'A coffee shop at Margaha Beach where visitors can enjoy sunrise and sunset views. Known for its relaxed atmosphere and local coffee offerings near the Nunelucio Alvarado Art Gallery.'],

            ['name' => 'Old Sagay Port',
             'category' => 'Cultural & Historical',
             'latitude' => 10.9494574, 'longitude' => 123.4283223,
             'location' => 'Brgy. Old Sagay',
             'description' => 'A historical maritime landmark that once served as a center for trade and transportation. Now serves as the jump-off point for island destinations like Carbin Reef and Suyac Island. Features scenic coastal views.'],

            ['name' => 'Pala-Pala sa Vito',
             'category' => 'Food & Dining',
             'latitude' => 10.905964, 'longitude' => 123.514339,
             'location' => 'Brgy. Vito',
             'description' => 'A "pala-pala" style restaurant where visitors can select sustainably caught seafood. Located in Brgy. Vito with an overlooking view of the Sagay Marine Reserve. Advocates slow food principles with no artificial flavoring.'],

            ['name' => 'Panal Reef',
             'category' => 'Marine & Island',
             'latitude' => 11.0166667, 'longitude' => 123.4166667,
             'location' => 'Sagay Marine Reserve',
             'description' => 'A reef system within the Sagay Marine Reserve. Known for its coral gardens and marine life diversity.'],

            ['name' => 'San Vicente Ferrer Parish-Shrine (Vito Church)',
             'category' => 'Cultural & Historical',
             'latitude' => 10.9034955, 'longitude' => 123.5153004,
             'location' => 'Brgy. Vito',
             'description' => 'A historic church founded in 1898. Known for its miraculous patron saint and is the city\'s most visited built heritage site.'],

            ['name' => 'St. Joseph Parish Church',
             'category' => 'Cultural & Historical',
             'latitude' => 10.8786489, 'longitude' => 123.405767,
             'location' => 'Sagay City Proper',
             'description' => 'A cornerstone of the local Catholic community under the Roman Catholic Diocese of San Carlos. Dedicated to St. Joseph, the Husband of Mary. Feast day celebrated on March 19 annually.'],

            ['name' => 'Suyac Island Mangrove Eco-Park',
             'category' => 'Marine & Island',
             'latitude' => 10.9504614, 'longitude' => 123.4545279,
             'location' => 'Suyac Island',
             'description' => 'A 15.6-hectare community-based eco-park featuring century-old Sonneratia alba mangroves (pagatpat). Features elevated bamboo boardwalks, kayaking opportunities, and has won the ASEAN Community-Based Tourism Award.'],
        ];

        foreach ($destinations as $dest) {
            TouristDestination::create(array_merge($dest, ['rating' => 0.0, 'reviews_count' => 0]));
        }

        $this->command->info('✓ Seeded ' . count($destinations) . ' tourist destinations for Sagay City.');
    }
}
