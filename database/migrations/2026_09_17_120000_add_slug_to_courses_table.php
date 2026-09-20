<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
        });

        DB::table('courses')
            ->select(['id', 'course_name', 'class_group'])
            ->orderBy('id')
            ->each(function (object $course): void {
                $base = Str::slug(trim($course->course_name.' '.$course->class_group)) ?: 'kelas';
                $slug = $base;
                $counter = 2;

                while (DB::table('courses')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$counter++;
                }

                DB::table('courses')->where('id', $course->id)->update(['slug' => $slug]);
            });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
