<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DropUniqueReviewConstraint extends Seeder
{
    public function run(): void
    {
        try {
            DB::statement('ALTER TABLE destination_reviews DROP INDEX unique_review');
            echo "✓ Successfully dropped unique_review constraint\n";
        } catch (\Exception $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }
}
