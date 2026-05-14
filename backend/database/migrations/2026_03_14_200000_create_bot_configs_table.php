<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateBotConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('bot_configs', function (Blueprint $table) {
            $table->id();
            $table->string('bot_key', 64)->unique()->comment('机器人唯一标识');
            $table->string('name', 128)->comment('显示名称');
            $table->string('avatar', 512)->nullable()->comment('头像路径');
            $table->timestamps();
        });

        // 插入 Alma 默认配置
        DB::table('bot_configs')->insert([
            'bot_key'    => 'alma',
            'name'       => 'Alma',
            'avatar'     => '/images/alma-avatar.svg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('bot_configs');
    }
}
