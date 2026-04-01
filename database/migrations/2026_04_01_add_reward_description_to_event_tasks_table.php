<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('event_tasks', 'reward_description')) {
                $table->text('reward_description')->nullable()->after('reward_points');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('event_tasks', 'reward_description')) {
                $table->dropColumn('reward_description');
            }
        });
    }
};
