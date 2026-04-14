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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open');              // open, in_progress, resolved, closed

            // Filled by the AI after analysis
            $table->string('ai_category')->nullable();              // billing, account, technical, etc
            $table->unsignedTinyInteger('ai_urgency')->nullable();  // 1–5
            $table->text('ai_suggested_reply')->nullable();
            $table->boolean('ai_auto_resolvable')->nullable();
            $table->json('ai_analysis')->nullable();                // full structured output stored raw

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
