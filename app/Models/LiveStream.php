<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'platform',
        'stream_url',
        'embed_url',
        'thumbnail',
        'status',
        'is_active',
        'is_featured',
        'donations_enabled',
        'donation_goal',
        'donation_call_to_action',
        'viewer_count',
        'scheduled_at',
        'started_at',
        'ended_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'donations_enabled' => 'boolean',
            'donation_goal' => 'decimal:2',
            'viewer_count' => 'integer',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function completedDonations(): HasMany
    {
        return $this->hasMany(Donation::class)->where('status', 'completed');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', 'live');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_featured', true);
    }

    /**
     * Determine whether donations are active for this stream and globally.
     */
    public function areDonationsActive(): bool
    {
        return (bool) $this->donations_enabled && (bool) SiteSetting::get('live_donations_enabled', true);
    }

    /**
     * Calculate total completed donations raised for this live stream.
     */
    public function totalDonationsRaised(): float
    {
        return (float) $this->completedDonations()->sum('amount');
    }

    /**
     * Calculate donation goal progress percentage (0 - 100).
     */
    public function donationProgressPercentage(): int
    {
        if (! $this->donation_goal || (float) $this->donation_goal <= 0) {
            return 0;
        }

        $percentage = (int) round(($this->totalDonationsRaised() / (float) $this->donation_goal) * 100);

        return min(100, max(0, $percentage));
    }

    /**
     * Resolve the iframe embed URL for YouTube, Twitch, or Facebook with autoplay enabled.
     */
    public function getComputedEmbedUrl(?string $parentHost = null): string
    {
        $host = $parentHost ?: (request()?->getHost() ?: 'localhost');
        if (in_array($host, ['0.0.0.0', '127.0.0.1', '::1'], true)) {
            $host = 'localhost';
        }

        $rawUrl = ! empty($this->embed_url)
            ? $this->embed_url
            : $this->stream_url;

        // If an HTML <iframe ... src="..."> snippet was pasted, extract its src URL
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', (string) $rawUrl, $matches)) {
            $rawUrl = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        } else {
            $rawUrl = self::resolveEmbedUrl($this->platform, (string) $rawUrl, $host);
        }

        return self::ensureAutoplayUrl($this->platform, (string) $rawUrl, $host);
    }

    /**
     * Convert a platform URL or iframe snippet (YouTube, Twitch, Facebook) into an embeddable iframe URL with autoplay.
     */
    public static function resolveEmbedUrl(string $platform, string $streamUrl, string $parentHost = 'localhost'): string
    {
        $url = trim($streamUrl);
        $platform = strtolower(trim($platform));

        if (in_array($parentHost, ['0.0.0.0', '127.0.0.1', '::1'], true)) {
            $parentHost = 'localhost';
        }

        // Extract src attribute if an <iframe> HTML tag was provided
        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $url, $matches)) {
            $url = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        if ($platform === 'youtube') {
            if (preg_match('/(?:youtube\.com\/(?:watch\?.*v=|live\/|shorts\/|v\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                return 'https://www.youtube.com/embed/'.$matches[1].'?autoplay=1&mute=1&playsinline=1&rel=0';
            }

            if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
                return 'https://www.youtube.com/embed/'.$url.'?autoplay=1&mute=1&playsinline=1&rel=0';
            }

            return self::ensureAutoplayUrl('youtube', $url, $parentHost);
        }

        if ($platform === 'twitch') {
            if (str_contains($url, 'player.twitch.tv')) {
                return self::ensureAutoplayUrl('twitch', $url, $parentHost);
            }

            if (preg_match('/twitch\.tv\/videos\/(\d+)/i', $url, $matches)) {
                return 'https://player.twitch.tv/?video='.$matches[1].'&parent='.urlencode($parentHost).'&autoplay=true';
            }

            if (preg_match('/twitch\.tv\/([a-zA-Z0-9_]+)/i', $url, $matches)) {
                return 'https://player.twitch.tv/?channel='.$matches[1].'&parent='.urlencode($parentHost).'&autoplay=true';
            }

            if (preg_match('/^[a-zA-Z0-9_]+$/', $url)) {
                return 'https://player.twitch.tv/?channel='.$url.'&parent='.urlencode($parentHost).'&autoplay=true';
            }

            return self::ensureAutoplayUrl('twitch', $url, $parentHost);
        }

        if ($platform === 'facebook') {
            if (str_contains($url, 'facebook.com/plugins/video.php')) {
                return self::ensureAutoplayUrl('facebook', $url, $parentHost);
            }

            return 'https://www.facebook.com/plugins/video.php?href='.urlencode($url).'&show_text=false&autoplay=true';
        }

        return $url;
    }

    /**
     * Guarantee autoplay query parameters on an existing embed URL.
     */
    public static function ensureAutoplayUrl(string $platform, string $url, string $parentHost = 'localhost'): string
    {
        $platform = strtolower(trim($platform));

        if (in_array($parentHost, ['0.0.0.0', '127.0.0.1', '::1'], true)) {
            $parentHost = 'localhost';
        }

        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $url, $matches)) {
            $url = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        if ($platform === 'youtube' || str_contains($url, 'youtube.com/embed/')) {
            if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                return 'https://www.youtube.com/embed/'.$matches[1].'?autoplay=1&mute=1&playsinline=1&rel=0';
            }

            if (! str_contains($url, 'autoplay=1')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.'autoplay=1&mute=1&playsinline=1';
            }

            return $url;
        }

        if ($platform === 'twitch' || str_contains($url, 'player.twitch.tv')) {
            $url = str_replace('autoplay=false', 'autoplay=true', $url);

            if (! str_contains($url, 'parent=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.'parent='.urlencode($parentHost);
            } else {
                $url = preg_replace('/parent=[^&]+/', 'parent='.urlencode($parentHost), $url) ?? $url;
            }

            if (! str_contains($url, 'autoplay=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.'autoplay=true';
            }

            return $url;
        }

        if ($platform === 'facebook' || str_contains($url, 'facebook.com/plugins/video.php')) {
            if (! str_contains($url, 'autoplay=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.'autoplay=true';
            }

            return $url;
        }

        return $url;
    }

    /**
     * Activate this stream as the single exclusive broadcast served to the public.
     */
    public static function activateOnly(self $stream): void
    {
        self::where('id', '!=', $stream->id)->update([
            'is_active' => false,
            'is_featured' => false,
        ]);

        $stream->update([
            'status' => 'live',
            'is_active' => true,
            'is_featured' => true,
            'started_at' => $stream->started_at ?? now(),
            'ended_at' => null,
        ]);
    }

    /**
     * Determine whether this stream is a Twitch stream.
     */
    public function isTwitch(): bool
    {
        $combined = strtolower((string) $this->platform.' '.(string) $this->stream_url.' '.(string) $this->embed_url);

        return strtolower($this->platform) === 'twitch'
            || str_contains($combined, 'twitch.tv')
            || str_contains($combined, 'twitch.player')
            || str_contains($combined, 'twitch.embed');
    }

    /**
     * Determine whether the admin provided an HTML <iframe> embed for Twitch.
     */
    public function usesTwitchIframe(): bool
    {
        if (! $this->isTwitch()) {
            return false;
        }

        $combined = strtolower((string) $this->embed_url.' '.(string) $this->stream_url);

        return str_contains($combined, '<iframe');
    }

    /**
     * Determine whether this Twitch stream should be rendered via the Twitch.Player JavaScript SDK.
     */
    public function usesTwitchJsPlayer(): bool
    {
        return $this->isTwitch() && ! $this->usesTwitchIframe();
    }

    /**
     * Extract Twitch.Player JavaScript configuration options (channel, video, or collection)
     * from a pasted Twitch JS embed snippet, URL, or channel handle.
     *
     * @return array<string, mixed>
     */
    public function getTwitchPlayerOptions(?string $parentHost = null): array
    {
        $host = $parentHost ?: (request()?->getHost() ?: 'localhost');
        if (in_array($host, ['0.0.0.0', '127.0.0.1', '::1'], true)) {
            $host = 'localhost';
        }

        // Prefer stream_url if it contains raw Twitch JS embed code, otherwise embed_url
        $rawStream = trim((string) $this->stream_url);
        $rawEmbed = trim((string) $this->embed_url);
        $source = (str_contains($rawStream, 'Twitch.') || str_contains($rawStream, '<script'))
            ? $rawStream
            : ($rawEmbed !== '' ? $rawEmbed : $rawStream);

        $options = [
            'width' => '100%',
            'height' => '100%',
            'autoplay' => true,
            'muted' => false,
            'parent' => [$host],
        ];

        // 1. Parse JavaScript embed object properties: channel: "xxx", video: "123", collection: "abc"
        if (preg_match('/["\']?channel["\']?\s*:\s*["\']([a-zA-Z0-9_]+)["\']/i', $source, $matches)) {
            $options['channel'] = $matches[1];

            return $options;
        }

        if (preg_match('/["\']?video["\']?\s*:\s*["\']?(?:v)?(\d+)["\']?/i', $source, $matches)) {
            $options['video'] = $matches[1];

            return $options;
        }

        if (preg_match('/["\']?collection["\']?\s*:\s*["\']([a-zA-Z0-9_-]+)["\']/i', $source, $matches)) {
            $options['collection'] = $matches[1];

            return $options;
        }

        // 2. Parse URL query parameters or path segments
        if (preg_match('/(?:twitch\.tv\/videos\/|[?&]video=)(\d+)/i', $source, $matches)) {
            $options['video'] = $matches[1];

            return $options;
        }

        if (preg_match('/[?&]collection=([a-zA-Z0-9_-]+)/i', $source, $matches)) {
            $options['collection'] = $matches[1];

            return $options;
        }

        if (preg_match('/[?&]channel=([a-zA-Z0-9_]+)/i', $source, $matches)) {
            $options['channel'] = $matches[1];

            return $options;
        }

        if (preg_match('/twitch\.tv\/([a-zA-Z0-9_]+)/i', $source, $matches)) {
            $options['channel'] = $matches[1];

            return $options;
        }

        if (preg_match('/^[a-zA-Z0-9_]+$/', $source)) {
            $options['channel'] = $source;

            return $options;
        }

        $options['channel'] = $source;

        return $options;
    }

    /**
     * Human-readable platform label.
     */
    public function platformLabel(): string
    {
        return match ($this->platform) {
            'youtube' => 'YouTube Live',
            'facebook' => 'Facebook Live',
            'twitch' => 'Twitch Stream',
            default => 'Custom Stream',
        };
    }
}
