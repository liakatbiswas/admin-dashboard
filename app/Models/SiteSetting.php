<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    private const CACHE_KEY = 'site_settings.current.attributes.v1';

    protected $fillable = [
        'site_title',
        'site_subtitle',
        'site_tagline',
        'site_description',
        'favicon_path',
        'light_logo_path',
        'dark_logo_path',
        'phone',
        'email',
        'address',
        'website',
        'updated_by',
    ];

    public static function current(): self
    {
        $attributes = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->findOrFail(1)->getAttributes()
        );

        return (new static)->newFromBuilder($attributes);
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('site_settings.current');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
