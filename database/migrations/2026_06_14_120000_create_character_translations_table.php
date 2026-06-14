<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->text('description');
            $table->longText('personality_prompt');
            $table->timestamps();
            $table->unique(['character_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_translations');
    }
};
