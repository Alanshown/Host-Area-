<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatPresencesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_presences', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 64)->default('public-lobby')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_typing')->default(false);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('typing_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['channel', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_presences');
    }
}