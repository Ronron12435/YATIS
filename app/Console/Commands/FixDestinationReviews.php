<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\TouristDestination;

class FixDestinationReviews extends Command
{
    protected $signature = 'fix:destination-reviews';
    protected $description = 'Fix destination review counts and ratings';

    public function handle()
    {
        $this->info('Fixing destination review counts...');

        // Get all destinations with review counts
        $reviews = DB::table('destination_reviews')
            ->selectRaw('destination_id, COUNT(*) as cnt, AVG(rating) as avg_rating')
            ->groupBy('destination_id')
            ->get();

        foreach ($reviews as $review) {
            TouristDestination::where('id', $review->destination_id)->update([
                'reviews_count' => $review->cnt,
                'rating' => round($review->avg_rating, 2),
            ]);
            $this->line("✓ Updated destination {$review->destination_id}: {$review->cnt} reviews, avg rating: " . round($review->avg_rating, 2));
        }

        // Also update destinations with no reviews
        $noReviews = TouristDestination::whereNotIn('id', $reviews->pluck('destination_id'))->get();
        foreach ($noReviews as $dest) {
            $dest->update([
                'reviews_count' => 0,
                'rating' => 0,
            ]);
            $this->line("✓ Reset destination {$dest->id}: 0 reviews");
        }

        $this->info('Done!');
    }
}
