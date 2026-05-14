<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMemoriesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_memories', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->default('public-lobby')->unique();
            $table->foreignId('last_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->longText('summary');
            $table->longText('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_memories');
    }
}