<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            // Index for getPendingRequests query
            $table->index(['friend_id', 'status']);
            // Index for getFriends query
            $table->index(['user_id', 'status']);
        });

        Schema::table('private_messages', function (Blueprint $table) {
            // Index for unread count query
            $table->index(['recipient_id', 'is_read']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for nearby active users query
            $table->index(['latitude', 'longitude']);
            $table->index(['online_status']);
        });
    }

    public function down(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            $table->dropIndex(['friend_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropIndex(['recipient_id', 'is_read']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['online_status']);
        });
    }
};
