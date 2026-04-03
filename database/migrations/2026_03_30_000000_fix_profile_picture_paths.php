<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extract just the filename from paths like '/storage/avatars/filename.png'
        DB::statement("
            UPDATE users 
            SET profile_picture = SUBSTRING_INDEX(profile_picture, '/', -1)
            WHERE profile_picture IS NOT NULL 
            AND profile_picture LIKE '/storage/avatars/%'
        ");
    }

    public function down(): void
    {
        // Revert to full paths
        DB::statement("
            UPDATE users 
            SET profile_picture = CONCAT('/storage/avatars/', profile_picture)
            WHERE profile_picture IS NOT NULL 
            AND profile_picture NOT LIKE '/storage/avatars/%'
            AND profile_picture NOT LIKE '%/%'
        ");
    }
};
