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
        Schema::ensureVectorExtensionExists(); // Ensure the pgvector extension exists before creating the column

        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('filename');                             // Original filename... for example, "password-reset.md"
            $table->text('content');                                // Raw markdown content
            $table->vector('embedding', dimensions: 1536)->index(); // 1536 dimensions matches text-embedding-3-small from OpenAI

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
