<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id', 20)->nullable()->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unique(['semester_id', 'course_name', 'class_group'], 'courses_semester_name_group_unique');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->unique(['course_id', 'meeting_number']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['meeting_id', 'student_id']);
        });

        Schema::table('final_tasks', function (Blueprint $table) {
            $table->unique('course_id');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->unique(['course_id', 'user_id']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->unique(['meeting_id', 'student_id'], 'submissions_meeting_student_unique');
            $table->unique(['final_task_id', 'student_id'], 'submissions_final_student_unique');
        });

        Schema::table('submission_histories', function (Blueprint $table) {
            $table->text('drive_link')->change();
            $table->unique(['submission_id', 'iteration']);
        });
    }

    public function down(): void
    {
        Schema::table('submission_histories', function (Blueprint $table) {
            $table->dropUnique(['submission_id', 'iteration']);
            $table->string('drive_link')->change();
        });
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropUnique('submissions_meeting_student_unique');
            $table->dropUnique('submissions_final_student_unique');
        });
        Schema::table('course_user', fn (Blueprint $table) => $table->dropUnique(['course_id', 'user_id']));
        Schema::table('final_tasks', fn (Blueprint $table) => $table->dropUnique(['course_id']));
        Schema::table('attendances', fn (Blueprint $table) => $table->dropUnique(['meeting_id', 'student_id']));
        Schema::table('meetings', fn (Blueprint $table) => $table->dropUnique(['course_id', 'meeting_number']));
        Schema::table('courses', fn (Blueprint $table) => $table->dropUnique('courses_semester_name_group_unique'));
        Schema::table('sessions', fn (Blueprint $table) => $table->string('user_id', 20)->nullable()->change());
    }
};
