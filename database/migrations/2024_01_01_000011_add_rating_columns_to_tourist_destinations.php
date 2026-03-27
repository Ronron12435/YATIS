<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_destinations', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('tourist_destinations', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0)->after('image');
            }
            if (!Schema::hasColumn('tourist_destinations', 'reviews_count')) {
                $table->integer('reviews_count')->default(0)->after('rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tourist_destinations', function (Blueprint $table) {
            $table->dropColumn(['rating', 'reviews_count']);
        });
    }
};
