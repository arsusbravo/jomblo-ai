<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedInteger('chatters_count')->default(0)->after('is_active');
        });

        // Backfill from existing data: count distinct (user, character) pairs that
        // ever sent a message. Matches the previous withCount() metric, so the
        // numbers don't reset on this migration.
        DB::statement(<<<'SQL'
            UPDATE characters
            SET chatters_count = (
                SELECT COUNT(c.id)
                FROM conversations c
                WHERE c.character_id = characters.id
                  AND EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('chatters_count');
        });
    }
};
