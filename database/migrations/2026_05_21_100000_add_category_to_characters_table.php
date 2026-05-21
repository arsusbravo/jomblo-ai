<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // MySQL applies the default to existing rows during ADD COLUMN —
            // every existing character is backfilled to 'realistic' implicitly.
            $table->enum('category', ['anime', 'realistic'])
                  ->default('realistic')
                  ->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
