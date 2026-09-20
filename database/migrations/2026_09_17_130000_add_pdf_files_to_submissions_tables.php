<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('submission_link');
            $table->string('original_filename')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('original_filename');
        });

        Schema::table('submission_histories', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('drive_link');
            $table->string('original_filename')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('submission_histories', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_filename', 'file_size']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_filename', 'file_size']);
        });
    }
};
