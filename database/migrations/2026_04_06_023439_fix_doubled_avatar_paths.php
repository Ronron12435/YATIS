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
        DB::statement("UPDATE users SET profile_picture = REPLACE(profile_picture, 'avatars/avatars/', 'avatars/') WHERE profile_picture LIKE 'avatars/avatars/%'");
        DB::statement("UPDATE users SET cover_photo = REPLACE(cover_photo, 'covers/covers/', 'covers/') WHERE cover_photo LIKE 'covers/covers/%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this
    }
};
