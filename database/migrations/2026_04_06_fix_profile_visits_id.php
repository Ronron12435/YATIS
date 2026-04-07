<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to add primary key and auto-increment
        DB::statement('ALTER TABLE profile_visits MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
    }

    public function down(): void
    {
        // Revert to regular int
        DB::statement('ALTER TABLE profile_visits MODIFY id INT NOT NULL');
    }
};
