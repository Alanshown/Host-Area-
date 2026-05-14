<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileVisitsTable extends Migration
{
    public function up()
    {
        Schema::create('profile_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('profile_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['visitor_id', 'profile_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('profile_visits');
    }
}