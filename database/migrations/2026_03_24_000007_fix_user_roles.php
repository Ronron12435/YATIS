<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set all users to 'user' role except admin (ID 7)
        DB::table('users')->where('id', '!=', 7)->update(['role' => 'user']);
    }

    public function down(): void
    {
        // Rollback not needed for this fix
    }
};
