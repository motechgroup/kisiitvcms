<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Load all settings once per request from a single memoized cache entry.
     *
     * @return array<string, mixed>
     */
    protected static function allCached(): array
    {
        return Cache::memo()->remember('site_settings_all', 3600, function (): array {
            $result = [];

            foreach (self::all(['key', 'value', 'type']) as $setting) {
                $result[$setting->key] = match ($setting->type) {
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $setting->value,
                    'json' => json_decode($setting->value, true) ?? [],
                    default => $setting->value,
                };
            }

            return $result;
        });
    }

    /**
     * Clear both memoized and persistent settings caches.
     */
    public static function clearCache(): void
    {
        Cache::memo()->forget('site_settings_all');
    }

    /**
     * Get a setting by key with single-query + in-memory caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::allCached();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    /**
     * Set a setting value and clear its cache.
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $serializedValue = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $serializedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("site_setting_{$key}");
        self::clearCache();

        return $setting;
    }

    /**
     * Get the normalized Google AdSense Publisher Client ID (e.g. ca-pub-XXXXXXXXXXXXXXXX).
     */
    public static function normalizedAdsenseClientId(): ?string
    {
        $raw = trim((string) self::get('adsense_client_id', ''));
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'pub-')) {
            return 'ca-'.$raw;
        }

        return $raw;
    }

    /**
     * Render an AdSense ad unit by setting key (accepts either full <ins>/<script> HTML or a numeric slot ID).
     */
    public static function renderAdsenseUnit(string $slotSettingKey): ?string
    {
        if (! (bool) self::get('adsense_enabled', false)) {
            return null;
        }

        $raw = trim((string) self::get($slotSettingKey, ''));
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, '<ins') || str_contains($raw, '<script') || str_contains($raw, '<iframe')) {
            return $raw;
        }

        $clientId = self::normalizedAdsenseClientId();
        if ($clientId && preg_match('/^\d+$/', $raw)) {
            $escapedClient = e($clientId);
            $escapedSlot = e($raw);

            return '<ins class="adsbygoogle" style="display:block" data-ad-client="'.$escapedClient.'" data-ad-slot="'.$escapedSlot.'" data-ad-format="auto" data-full-width-responsive="true"></ins>'
                .'<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
        }

        return $raw;
    }
}
