<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotConfig extends Model
{
    protected $table = 'bot_configs';

    protected $fillable = ['bot_key', 'name', 'avatar'];

    /** @var array<string, self|null> */
    private static array $cache = [];

    /**
     * 根据 bot_key 获取配置（带简单静态缓存，每次请求生命周期内有效）
     */
    public static function forKey(string $key): ?self
    {
        if (! array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::where('bot_key', $key)->first();
        }
        return self::$cache[$key];
    }

    /**
     * 更新头像并刷新静态缓存
     */
    public static function updateAvatar(string $key, string $avatarPath): self
    {
        $bot = self::firstOrCreate(['bot_key' => $key], ['name' => ucfirst($key)]);
        $bot->update(['avatar' => $avatarPath]);
        self::$cache[$key] = $bot->fresh();
        return $bot;
    }
}
