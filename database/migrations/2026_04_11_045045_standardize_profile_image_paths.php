<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove /storage/ prefix if it exists
        DB::statement("UPDATE users SET profile_picture = REPLACE(profile_picture, '/storage/', '') WHERE profile_picture LIKE '/storage/%'");
        DB::statement("UPDATE users SET cover_photo = REPLACE(cover_photo, '/storage/', '') WHERE cover_photo LIKE '/storage/%'");
        
        // Ensure all paths start with avatars/ or covers/
        DB::statement("UPDATE users SET profile_picture = CONCAT('avatars/', profile_picture) WHERE profile_picture IS NOT NULL AND profile_picture != '' AND profile_picture NOT LIKE 'avatars/%'");
        DB::statement("UPDATE users SET cover_photo = CONCAT('covers/', cover_photo) WHERE cover_photo IS NOT NULL AND cover_photo != '' AND cover_photo NOT LIKE 'covers/%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the prefixes to revert
        DB::statement("UPDATE users SET profile_picture = REPLACE(profile_picture, 'avatars/', '') WHERE profile_picture LIKE 'avatars/%'");
        DB::statement("UPDATE users SET cover_photo = REPLACE(cover_photo, 'covers/', '') WHERE cover_photo LIKE 'covers/%'");
    }
};
