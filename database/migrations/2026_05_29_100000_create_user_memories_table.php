<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_memories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // NULL  = global (applies across all characters)
            // value = scoped to one specific character
            $table->foreignId('character_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();

            // NULL  = accumulating event (insert, no overwrite)
            // value = keyed fact that can be updated ("job", "city", "age")
            $table->string('key')->nullable();

            $table->longText('value');

            // identity | preference | relationship | life_event | boundary
            $table->string('category');

            $table->foreignId('source_conversation_id')
                  ->nullable()
                  ->constrained('conversations')
                  ->nullOnDelete();

            $table->timestamps();

            // Deduplication is handled in application code — no DB-level unique
            // constraint because MySQL cannot do partial indexes (WHERE key IS NOT NULL)
            // and nullable columns in unique indexes behave inconsistently across engines.
            $table->index(['user_id', 'character_id', 'key'], 'um_user_char_key_idx');
            $table->index(['user_id', 'character_id', 'created_at'], 'um_user_char_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_memories');
    }
};
