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
        Schema::create('submission_histories', function (Blueprint $table) {
            $table->id();

            // GUNAKAN DEFINISI MANUAL AGAR TIPE DATA IDENTIK
            $table->unsignedBigInteger('submission_id');
            $table->foreign('submission_id')
                ->references('id')
                ->on('submissions')
                ->onDelete('cascade');

            $table->text('drive_link');
            $table->integer('iteration')->default(1);
            $table->text('feedback')->nullable();
            $table->enum('action_type', ['Upload', 'Revision', 'ACC']);

            // Gunakan string(20) karena merujuk ke ID User Anda
            $table->string('reviewed_by', 20)->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_histories');
    }
};
