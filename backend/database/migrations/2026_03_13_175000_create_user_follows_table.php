<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFollowsTable extends Migration
{
    public function up()
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('followed_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['follower_id', 'followed_user_id']);
            $table->index(['followed_user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_follows');
    }
}