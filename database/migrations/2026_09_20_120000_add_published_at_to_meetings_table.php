<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->after('deadline')->index();
        });

        // Preserve the availability of every legacy module. New placeholders created
        // after this migration explicitly start as drafts in the course workflow.
        DB::table('meetings')
            ->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropIndex(['published_at']);
            $table->dropColumn('published_at');
        });
    }
};
