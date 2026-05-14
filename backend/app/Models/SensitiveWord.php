<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SensitiveWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'category',
        'level',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const CATEGORY_CUSTOM = 'custom';
    public const CATEGORY_ABUSE = 'abuse';
    public const CATEGORY_VIOLENCE = 'violence';
    public const CATEGORY_PORN = 'porn';
    public const CATEGORY_POLITICS = 'politics';

    public const LEVEL_WARNING = 'warning';
    public const LEVEL_MUTE = 'mute';
    public const LEVEL_BAN = 'ban';

    public static array $categories = [
        self::CATEGORY_CUSTOM => '自定义',
        self::CATEGORY_ABUSE => '辱骂攻击',
        self::CATEGORY_VIOLENCE => '暴力血腥',
        self::CATEGORY_PORN => '色情低俗',
        self::CATEGORY_POLITICS => '政治敏感',
    ];

    public static array $levels = [
        self::LEVEL_WARNING => '警告',
        self::LEVEL_MUTE => '禁言',
        self::LEVEL_BAN => '封禁',
    ];

    public static array $levelDurations = [
        self::LEVEL_WARNING => 0,
        self::LEVEL_MUTE => 30,
        self::LEVEL_BAN => 1440,
    ];

    public static function getActiveWords(): array
    {
        return static::where('is_active', true)
            ->pluck('word')
            ->toArray();
    }

    public static function checkContent(string $content): array
    {
        $activeWords = static::getActiveWords();
        $matches = [];

        foreach ($activeWords as $word) {
            if (mb_stripos($content, $word) !== false) {
                $record = static::where('word', $word)->first();
                $matches[] = [
                    'word' => $word,
                    'category' => $record->category ?? 'custom',
                    'level' => $record->level ?? 'warning',
                ];
            }
        }

        return $matches;
    }
}
