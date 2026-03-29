<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes for frequently queried columns (only if they don't exist)
        Schema::table('job_applications', function (Blueprint $table) {
            if (!$this->indexExists('job_applications', 'job_applications_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('job_applications', 'job_applications_job_posting_id_index')) {
                $table->index('job_posting_id');
            }
            if (!$this->indexExists('job_applications', 'job_applications_user_id_job_posting_id_index')) {
                $table->index(['user_id', 'job_posting_id']);
            }
        });

        Schema::table('job_postings', function (Blueprint $table) {
            if (!$this->indexExists('job_postings', 'job_postings_employer_id_index')) {
                $table->index('employer_id');
            }
            if (!$this->indexExists('job_postings', 'job_postings_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('job_postings', 'job_postings_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('friendships', function (Blueprint $table) {
            if (!$this->indexExists('friendships', 'friendships_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('friendships', 'friendships_friend_id_index')) {
                $table->index('friend_id');
            }
            if (!$this->indexExists('friendships', 'friendships_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('private_messages', function (Blueprint $table) {
            if (!$this->indexExists('private_messages', 'private_messages_recipient_id_index')) {
                $table->index('recipient_id');
            }
            if (!$this->indexExists('private_messages', 'private_messages_sender_id_index')) {
                $table->index('sender_id');
            }
            if (!$this->indexExists('private_messages', 'private_messages_is_read_index')) {
                $table->index('is_read');
            }
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            if (!$this->indexExists('user_achievements', 'user_achievements_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('user_achievements', 'user_achievements_event_id_index')) {
                $table->index('event_id');
            }
        });

        Schema::table('user_task_completions', function (Blueprint $table) {
            if (!$this->indexExists('user_task_completions', 'user_task_completions_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('user_task_completions', 'user_task_completions_task_id_index')) {
                $table->index('task_id');
            }
            if (!$this->indexExists('user_task_completions', 'user_task_completions_event_id_index')) {
                $table->index('event_id');
            }
        });

        Schema::table('businesses', function (Blueprint $table) {
            if (!$this->indexExists('businesses', 'businesses_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('businesses', 'businesses_category_index')) {
                $table->index('category');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['job_posting_id']);
            $table->dropIndex(['user_id', 'job_posting_id']);
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndex(['employer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('friendships', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['friend_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropIndex(['recipient_id']);
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['is_read']);
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['event_id']);
        });

        Schema::table('user_task_completions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['task_id']);
            $table->dropIndex(['event_id']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category']);
        });
    }
};
