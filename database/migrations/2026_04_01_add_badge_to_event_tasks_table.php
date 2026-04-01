<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('badge')->nullable()->default('🏆')->after('reward_points');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn('badge');
        });
    }
};
