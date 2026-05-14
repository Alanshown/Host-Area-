<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 512);
            $table->text('answer');
            $table->string('category', 64)->default('general');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
            $table->fullText(['question', 'answer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_faqs');
    }
};
