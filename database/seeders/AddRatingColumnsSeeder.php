<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRatingColumnsSeeder extends Seeder
{
    public function run(): void
    {
        // Add rating column if it doesn't exist
        if (!Schema::hasColumn('tourist_destinations', 'rating')) {
            DB::statement('ALTER TABLE tourist_destinations ADD COLUMN rating DECIMAL(3,2) DEFAULT 0 AFTER image');
            $this->command->info('Added rating column');
        }

        // Add reviews_count column if it doesn't exist
        if (!Schema::hasColumn('tourist_destinations', 'reviews_count')) {
            DB::statement('ALTER TABLE tourist_destinations ADD COLUMN reviews_count INT DEFAULT 0 AFTER rating');
            $this->command->info('Added reviews_count column');
        }

        // Update the columns with existing review data
        DB::statement('
            UPDATE tourist_destinations td
            SET rating = COALESCE((
                SELECT ROUND(AVG(rating), 2) FROM destination_reviews WHERE destination_id = td.id
            ), 0),
            reviews_count = COALESCE((
                SELECT COUNT(*) FROM destination_reviews WHERE destination_id = td.id
            ), 0)
        ');

        $this->command->info('Updated review counts and ratings');
    }
}
