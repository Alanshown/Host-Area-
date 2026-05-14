<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFaq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'category',
        'is_active',
        'hit_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hit_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function incrementHit(): void
    {
        $this->increment('hit_count');
    }

    public static function findBestMatch(string $query): ?self
    {
        $q = strtolower(trim($query));

        // 精确匹配（忽略大小写）
        $exact = static::active()
            ->whereRaw('LOWER(question) = ?', [$q])
            ->first();

        if ($exact) {
            return $exact;
        }

        // 包含匹配
        $contains = static::active()
            ->whereRaw('LOWER(question) LIKE ?', ["%{$q}%"])
            ->orderByDesc('hit_count')
            ->first();

        if ($contains) {
            return $contains;
        }

        // 关键词匹配（把 query 分词）
        $keywords = preg_split('/\s+/u', $q);
        $keywords = array_filter($keywords, fn($w) => mb_strlen($w) >= 2);

        if (! empty($keywords)) {
            $keyword = $keywords[0];
            return static::active()
                ->whereRaw('LOWER(question) LIKE ?', ["%{$keyword}%"])
                ->orderByDesc('hit_count')
                ->first();
        }

        return null;
    }
}
