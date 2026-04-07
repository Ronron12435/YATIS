<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_task_completions', function (Blueprint $table) {
            // Drop the old foreign keys with cascade delete
            $table->dropForeign(['event_id']);
            $table->dropForeign(['task_id']);
            
            // Re-add them without cascade delete to preserve user progress
            $table->foreign('event_id')->references('id')->on('events')->restrictOnDelete();
            $table->foreign('task_id')->references('id')->on('event_tasks')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_task_completions', function (Blueprint $table) {
            // Revert to cascade delete
            $table->dropForeign(['event_id']);
            $table->dropForeign(['task_id']);
            
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('event_tasks')->onDelete('cascade');
        });
    }
};
