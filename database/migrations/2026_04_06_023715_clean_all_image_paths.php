<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean profile_picture: remove all prefixes and keep only the filename
        DB::statement("UPDATE users SET profile_picture = SUBSTRING_INDEX(profile_picture, '/', -1) WHERE profile_picture IS NOT NULL AND profile_picture != ''");
        
        // Clean cover_photo: remove all prefixes and keep only the filename
        DB::statement("UPDATE users SET cover_photo = SUBSTRING_INDEX(cover_photo, '/', -1) WHERE cover_photo IS NOT NULL AND cover_photo != ''");
        
        // Now add the correct paths
        DB::statement("UPDATE users SET profile_picture = CONCAT('avatars/', profile_picture) WHERE profile_picture IS NOT NULL AND profile_picture != '' AND profile_picture NOT LIKE 'avatars/%'");
        DB::statement("UPDATE users SET cover_photo = CONCAT('covers/', cover_photo) WHERE cover_photo IS NOT NULL AND cover_photo != '' AND cover_photo NOT LIKE 'covers/%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this
    }
};
