<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedInteger('relationship_score')->default(0)->after('character_id');
            $table->unsignedInteger('streak_days')->default(0)->after('relationship_score');
            $table->date('last_streak_date')->nullable()->after('streak_days');
            $table->unsignedTinyInteger('unlocked_photo_level')->default(0)->after('last_streak_date');
        });

        // Grandfather conversations that already have generated photos —
        // they should immediately have camera access (level 3).
        DB::statement("
            UPDATE conversations
            SET relationship_score = 100, unlocked_photo_level = 3
            WHERE id IN (SELECT DISTINCT conversation_id FROM messages WHERE type = 'image')
        ");
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['relationship_score', 'streak_days', 'last_streak_date', 'unlocked_photo_level']);
        });
    }
};
