<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destination_reviews', function (Blueprint $table) {
            // Add image column if it doesn't exist
            if (!Schema::hasColumn('destination_reviews', 'image')) {
                $table->string('image')->nullable()->after('review');
            }
            
            // Add updated_at column if it doesn't exist
            if (!Schema::hasColumn('destination_reviews', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('destination_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('destination_reviews', 'image')) {
                $table->dropColumn('image');
            }
            if (Schema::hasColumn('destination_reviews', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
