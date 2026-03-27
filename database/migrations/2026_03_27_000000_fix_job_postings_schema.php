<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to job_postings table
        Schema::table('job_postings', function (Blueprint $table) {
            if (!Schema::hasColumn('job_postings', 'employer_id')) {
                $table->foreignId('employer_id')->after('id')->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('job_postings', 'job_type')) {
                $table->string('job_type')->after('title')->nullable();
            }
            if (!Schema::hasColumn('job_postings', 'salary_range')) {
                $table->string('salary_range')->after('job_type')->nullable();
            }
            if (!Schema::hasColumn('job_postings', 'requirements')) {
                $table->text('requirements')->after('description')->nullable();
            }
            if (!Schema::hasColumn('job_postings', 'status')) {
                $table->enum('status', ['open', 'closed'])->default('open')->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            if (Schema::hasColumn('job_postings', 'employer_id')) {
                $table->dropForeign(['employer_id']);
                $table->dropColumn('employer_id');
            }
            if (Schema::hasColumn('job_postings', 'job_type')) {
                $table->dropColumn('job_type');
            }
            if (Schema::hasColumn('job_postings', 'salary_range')) {
                $table->dropColumn('salary_range');
            }
            if (Schema::hasColumn('job_postings', 'requirements')) {
                $table->dropColumn('requirements');
            }
            if (Schema::hasColumn('job_postings', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
