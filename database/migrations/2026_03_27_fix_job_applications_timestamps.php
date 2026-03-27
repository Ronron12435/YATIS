<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // Add applied_at column if it doesn't exist
            if (!Schema::hasColumn('job_applications', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->after('status');
            }
        });

        // Copy created_at to applied_at if applied_at is empty
        DB::table('job_applications')
            ->whereNull('applied_at')
            ->update(['applied_at' => DB::raw('created_at')]);

        // Drop created_at and updated_at columns
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('job_applications', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // Restore timestamps
            $table->timestamps();
            
            // Drop applied_at if it exists
            if (Schema::hasColumn('job_applications', 'applied_at')) {
                $table->dropColumn('applied_at');
            }
        });
    }
};
