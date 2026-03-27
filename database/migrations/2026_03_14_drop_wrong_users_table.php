<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration is intentionally empty
        // The users table already exists with the correct structure
        // We just need to skip the default Laravel migration
    }

    public function down(): void
    {
        //
    }
};
