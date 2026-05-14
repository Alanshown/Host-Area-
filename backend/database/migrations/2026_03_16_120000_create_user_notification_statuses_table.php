<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserNotificationStatusesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_notification_statuses')) {
            return;
        }

        Schema::create('user_notification_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('notification_key', 191);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'notification_key']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_notification_statuses');
    }
}
