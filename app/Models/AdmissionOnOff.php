<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdmissionOnOff extends Model
{
    protected $table = 'admission_on_off';

    protected $fillable = ['is_on', 'deadline'];

    protected $casts = ['is_on' => 'boolean', 'deadline' => 'datetime'];

    public static function isOn(): bool
    {
        $settings = static::query()->find(1);

        return $settings && $settings->is_on && ($settings->deadline === null || now()->lt($settings->deadline));
    }

    public static function set(bool $enabled): void
    {
        static::query()->updateOrCreate(['id' => 1], ['is_on' => $enabled]);
        Cache::forget('admission_on_off.is_on');
    }
}
