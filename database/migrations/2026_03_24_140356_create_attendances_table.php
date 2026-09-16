<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // Gunakan unsignedBigInteger untuk ID yang auto-increment (meetings.id)
            $table->unsignedBigInteger('meeting_id');
            $table->foreign('meeting_id')
                ->references('id')
                ->on('meetings')
                ->onDelete('cascade');

            // Gunakan string(20) untuk ID User kamu
            $table->string('student_id', 20);
            $table->foreign('student_id')
                ->references('id')
                ->on('users');

            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'H', 'S', 'I', 'TK', 'Alpha']);
            $table->date('attendance_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
