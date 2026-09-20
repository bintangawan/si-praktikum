<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_staff_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            foreach (['changed_by', 'previous_dosen_id', 'previous_aslab_id', 'dosen_id', 'aslab_id'] as $column) {
                $table->string($column, 20);
            }
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_staff_histories');
    }
};
