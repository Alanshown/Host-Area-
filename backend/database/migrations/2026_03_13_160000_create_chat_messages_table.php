<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->default('public-lobby')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->string('author_name', 100);
            $table->string('author_role', 32)->default('user')->index();
            $table->string('message_type', 32)->default('message');
            $table->longText('content')->nullable();
            $table->longText('attachments')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamps();

            $table->index(['channel', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}