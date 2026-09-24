<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->decimal('aslab_score', 5, 2)->nullable()->after('dosen_status');
            $table->decimal('laboran_score', 5, 2)->nullable()->after('aslab_score');
        });

        Schema::create('course_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('student_id', 20);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->decimal('uts_score', 5, 2);
            $table->decimal('uas_score', 5, 2);
            $table->timestamps();
            $table->unique(['course_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_grades');

        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropColumn(['aslab_score', 'laboran_score']);
        });
    }
};
