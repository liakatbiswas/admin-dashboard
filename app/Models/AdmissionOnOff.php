<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdmissionOnOff extends Model
{
    protected $table = 'admission_on_off';

    protected $casts = ['is_on' => 'boolean'];

    public static function isOn(): bool
    {
        return Cache::rememberForever(
            'admission_on_off.is_on',
            fn () => (bool) static::query()->whereKey(1)->value('is_on')
        );
    }

    public static function set(bool $enabled): void
    {
        static::query()->updateOrCreate(['id' => 1], ['is_on' => $enabled]);
        Cache::forget('admission_on_off.is_on');
    }
}
