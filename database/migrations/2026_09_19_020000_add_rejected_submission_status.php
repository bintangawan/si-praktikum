<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('aslab_status', ['Pending', 'Revisi', 'Ditolak', 'ACC'])->default('Pending')->change();
            $table->enum('laboran_status', ['Pending', 'Revisi', 'Ditolak', 'ACC'])->default('Pending')->change();
            $table->enum('dosen_status', ['N/A', 'Pending', 'Revisi', 'Ditolak', 'ACC'])->default('N/A')->change();
        });

        Schema::table('submission_histories', function (Blueprint $table) {
            $table->enum('action_type', ['Upload', 'Revision', 'Rejected', 'ACC'])->change();
        });
    }

    public function down(): void
    {
        DB::table('submissions')->where('aslab_status', 'Ditolak')->update(['aslab_status' => 'Revisi']);
        DB::table('submissions')->where('laboran_status', 'Ditolak')->update(['laboran_status' => 'Revisi']);
        DB::table('submissions')->where('dosen_status', 'Ditolak')->update(['dosen_status' => 'Revisi']);
        DB::table('submission_histories')->where('action_type', 'Rejected')->update(['action_type' => 'Revision']);

        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('aslab_status', ['Pending', 'Revisi', 'ACC'])->default('Pending')->change();
            $table->enum('laboran_status', ['Pending', 'Revisi', 'ACC'])->default('Pending')->change();
            $table->enum('dosen_status', ['N/A', 'Pending', 'Revisi', 'ACC'])->default('N/A')->change();
        });

        Schema::table('submission_histories', function (Blueprint $table) {
            $table->enum('action_type', ['Upload', 'Revision', 'ACC'])->change();
        });
    }
};
