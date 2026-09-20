<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'approved_at', 'name'], 'users_role_approval_name_index');
        });
        Schema::table('semesters', function (Blueprint $table) {
            $table->index('is_active', 'semesters_active_index');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->index(['semester_id', 'created_at'], 'courses_semester_created_index');
            $table->index(['semester_id', 'laboran_id'], 'courses_semester_laboran_index');
            $table->index(['semester_id', 'dosen_id'], 'courses_semester_dosen_index');
            $table->index(['semester_id', 'aslab_id'], 'courses_semester_aslab_index');
        });
        Schema::table('course_user', function (Blueprint $table) {
            $table->index(['user_id', 'course_id'], 'course_user_user_course_index');
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(
                ['is_final', 'aslab_status', 'laboran_status', 'dosen_status', 'created_at'],
                'submissions_review_queue_index'
            );
        });
        Schema::table('tutorials', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'tutorials_type_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('tutorials', fn (Blueprint $table) => $table->dropIndex('tutorials_type_created_index'));
        Schema::table('submissions', fn (Blueprint $table) => $table->dropIndex('submissions_review_queue_index'));
        Schema::table('course_user', fn (Blueprint $table) => $table->dropIndex('course_user_user_course_index'));
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_semester_created_index');
            $table->dropIndex('courses_semester_laboran_index');
            $table->dropIndex('courses_semester_dosen_index');
            $table->dropIndex('courses_semester_aslab_index');
        });
        Schema::table('semesters', fn (Blueprint $table) => $table->dropIndex('semesters_active_index'));
        Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_role_approval_name_index'));
    }
};
