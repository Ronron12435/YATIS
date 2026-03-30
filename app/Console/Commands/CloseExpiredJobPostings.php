<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use Illuminate\Console\Command;

class CloseExpiredJobPostings extends Command
{
    protected $signature = 'jobs:close-expired';
    protected $description = 'Automatically close job postings that have passed their deadline';

    public function handle()
    {
        $updated = JobPosting::where('status', 'open')
            ->where('deadline', '<', now())
            ->update(['status' => 'closed']);

        $this->info("Closed {$updated} expired job postings.");
    }
}
