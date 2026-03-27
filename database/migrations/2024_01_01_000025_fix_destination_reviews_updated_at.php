<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destination_reviews', function (Blueprint $table) {
            // Make updated_at nullable since we don't update reviews
            $table->timestamp('updated_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('destination_reviews', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable(false)->change();
        });
    }
};
