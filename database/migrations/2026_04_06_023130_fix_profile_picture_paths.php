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
        DB::statement("UPDATE users SET profile_picture = CONCAT('avatars/', profile_picture) WHERE profile_picture IS NOT NULL AND profile_picture NOT LIKE 'avatars/%'");
        DB::statement("UPDATE users SET cover_photo = CONCAT('covers/', cover_photo) WHERE cover_photo IS NOT NULL AND cover_photo NOT LIKE 'covers/%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET profile_picture = REPLACE(profile_picture, 'avatars/', '') WHERE profile_picture LIKE 'avatars/%'");
        DB::statement("UPDATE users SET cover_photo = REPLACE(cover_photo, 'covers/', '') WHERE cover_photo LIKE 'covers/%'");
    }
};
