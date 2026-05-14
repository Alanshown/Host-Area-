<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatChannelMutesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_channel_mutes', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('muted_until')->index();
            $table->timestamps();

            $table->unique(['channel', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_channel_mutes');
    }
}