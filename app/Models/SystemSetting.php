<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    /**
     * Get setting value by key.
     * Uses Cache to prevent DB hits on every request.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => (bool) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    /**
     * Set setting value.
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
            Cache::forget("setting.{$key}");
        }
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }
}
