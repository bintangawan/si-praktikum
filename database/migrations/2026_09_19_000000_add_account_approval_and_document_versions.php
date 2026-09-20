<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 20)->nullable();
        });
        // Preserve access for existing accounts. New self-registrations require staff approval.
        DB::table('users')->update(['approved_at' => now()]);
        Schema::table('submissions', fn (Blueprint $table) => $table->unsignedInteger('document_version')->default(1));
        Schema::table('submission_histories', fn (Blueprint $table) => $table->unsignedInteger('document_version')->default(1));
        DB::table('submissions')->orderBy('id')->chunkById(100, function ($submissions) {
            foreach ($submissions as $submission) {
                $version = 0;
                foreach (DB::table('submission_histories')->where('submission_id', $submission->id)->orderBy('iteration')->get() as $history) {
                    if ($history->reviewed_by === null) {
                        $version++;
                    }
                    DB::table('submission_histories')->where('id', $history->id)->update(['document_version' => max(1, $version)]);
                }
                DB::table('submissions')->where('id', $submission->id)->update(['document_version' => max(1, $version)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('submission_histories', fn (Blueprint $table) => $table->dropColumn('document_version'));
        Schema::table('submissions', fn (Blueprint $table) => $table->dropColumn('document_version'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['approved_at', 'approved_by']));
    }
};
