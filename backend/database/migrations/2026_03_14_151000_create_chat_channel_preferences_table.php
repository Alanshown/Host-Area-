<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatChannelPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_channel_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('theme_variant', 32)->default('classic');
            $table->string('custom_background_path')->nullable();
            $table->boolean('hide_bot')->default(false);
            $table->timestamps();

            $table->unique(['channel', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_channel_preferences');
    }
}