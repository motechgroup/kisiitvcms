<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use App\Models\StreamAccessPass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LiveStreamController extends Controller
{
    /**
     * Display the live stream management console.
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $streams = LiveStream::with('creator')
            ->withCount(['donations', 'completedDonations', 'paidAccessPasses'])
            ->withSum('completedDonations', 'amount')
            ->withSum('paidAccessPasses', 'amount')
            ->orderByDesc('is_active')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->get();

        $liveCount = $streams->where('status', 'live')->where('is_active', true)->count();
        $scheduledCount = $streams->where('status', 'scheduled')->count();
        $totalDonationsRaised = (float) Donation::completed()->sum('amount');
        $totalSodRevenue = (float) StreamAccessPass::where('status', 'paid')->sum('amount');
        $globalDonationsEnabled = (bool) SiteSetting::get('live_donations_enabled', true);
        $activeGateways = PaymentGateway::active()->get();

        return view('admin.streams.index', compact(
            'streams',
            'liveCount',
            'scheduledCount',
            'totalDonationsRaised',
            'totalSodRevenue',
            'globalDonationsEnabled',
            'activeGateways'
        ));
    }

    /**
     * Store a new live stream supporting YouTube, Facebook, Twitch (JS Embed), or custom embed.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:live_streams,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'platform' => ['required', 'string', 'in:youtube,facebook,twitch,custom'],
            'stream_url' => ['required_without:embed_url', 'nullable', 'string', 'max:5000'],
            'embed_url' => ['required_without:stream_url', 'nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:scheduled,live,ended'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_sod' => ['nullable', 'boolean'],
            'sod_price' => ['nullable', 'numeric', 'min:0'],
            'sod_currency' => ['nullable', 'string', 'max:10'],
            'serve_on_main_live' => ['nullable', 'boolean'],
            'donations_enabled' => ['nullable', 'boolean'],
            'donation_goal' => ['nullable', 'numeric', 'min:0'],
            'donation_call_to_action' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $baseSlug = $slug ?: 'live-stream';
        $counter = 1;
        while (LiveStream::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $platform = $validated['platform'];
        $rawStreamUrl = trim((string) ($validated['stream_url'] ?? ''));
        $rawEmbedUrl = trim((string) ($validated['embed_url'] ?? ''));

        if ($platform === 'twitch') {
            $embedCode = $rawEmbedUrl !== '' ? $rawEmbedUrl : $rawStreamUrl;
            $streamUrl = $rawStreamUrl !== '' ? $rawStreamUrl : $embedCode;
            $embedUrl = $embedCode;
        } else {
            $streamUrl = $rawStreamUrl !== '' ? $rawStreamUrl : $rawEmbedUrl;
            $embedUrl = $rawEmbedUrl !== ''
                ? $rawEmbedUrl
                : LiveStream::resolveEmbedUrl($platform, $streamUrl, $request->getHost());
        }

        $isSod = $request->boolean('is_sod', false);
        $serveOnMainLive = $isSod
            ? $request->boolean('serve_on_main_live', false)
            : true;

        $serveToPublic = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $status = $validated['status'] ?? ($serveToPublic ? 'live' : 'scheduled');

        // Only replace the main /live broadcast when this stream is served on main /live
        if ($serveToPublic && $status === 'live' && $serveOnMainLive) {
            LiveStream::where('serve_on_main_live', true)->update([
                'is_active' => false,
                'is_featured' => false,
            ]);
        }

        LiveStream::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'platform' => $platform,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'status' => $status,
            'is_active' => $serveToPublic,
            'is_featured' => $serveToPublic && $serveOnMainLive,
            'is_sod' => $isSod,
            'sod_price' => $isSod ? (float) ($validated['sod_price'] ?? 0) : 0,
            'sod_currency' => strtoupper((string) ($validated['sod_currency'] ?? 'KES')),
            'serve_on_main_live' => $serveOnMainLive,
            'donations_enabled' => $isSod ? false : $request->boolean('donations_enabled', true),
            'donation_goal' => $validated['donation_goal'] ?? null,
            'donation_call_to_action' => $validated['donation_call_to_action'] ?? 'Support Kisii TV Live Coverage',
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'started_at' => $status === 'live' ? now() : null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.streams.index')
            ->with('success', "Live stream '{$validated['title']}' saved and configured.");
    }

    /**
     * Update an existing live stream.
     */
    public function update(Request $request, LiveStream $liveStream): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'platform' => ['required', 'string', 'in:youtube,facebook,twitch,custom'],
            'stream_url' => ['required_without:embed_url', 'nullable', 'string', 'max:5000'],
            'embed_url' => ['required_without:stream_url', 'nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:scheduled,live,ended'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_sod' => ['nullable', 'boolean'],
            'sod_price' => ['nullable', 'numeric', 'min:0'],
            'sod_currency' => ['nullable', 'string', 'max:10'],
            'serve_on_main_live' => ['nullable', 'boolean'],
            'donations_enabled' => ['nullable', 'boolean'],
            'donation_goal' => ['nullable', 'numeric', 'min:0'],
            'donation_call_to_action' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $platform = $validated['platform'];
        $rawStreamUrl = trim((string) ($validated['stream_url'] ?? ''));
        $rawEmbedUrl = trim((string) ($validated['embed_url'] ?? ''));

        if ($platform === 'twitch') {
            $embedCode = $rawEmbedUrl !== '' ? $rawEmbedUrl : $rawStreamUrl;
            $streamUrl = $rawStreamUrl !== '' ? $rawStreamUrl : $embedCode;
            $embedUrl = $embedCode;
        } else {
            $streamUrl = $rawStreamUrl !== '' ? $rawStreamUrl : $rawEmbedUrl;
            $embedUrl = $rawEmbedUrl !== ''
                ? $rawEmbedUrl
                : LiveStream::resolveEmbedUrl($platform, $streamUrl, $request->getHost());
        }

        $isSod = $request->boolean('is_sod', false);
        $serveOnMainLive = $isSod
            ? $request->boolean('serve_on_main_live', false)
            : true;

        $serveToPublic = $request->boolean('is_active', false);
        $status = $validated['status'] ?? ($serveToPublic ? 'live' : 'ended');

        // Only replace the main /live broadcast when served on main /live
        if ($serveToPublic && $serveOnMainLive) {
            LiveStream::where('id', '!=', $liveStream->id)
                ->where('serve_on_main_live', true)
                ->update([
                    'is_active' => false,
                    'is_featured' => false,
                ]);
            $status = 'live';
        } elseif ($serveToPublic) {
            $status = 'live';
        }

        $startedAt = $liveStream->started_at;
        $endedAt = $liveStream->ended_at;

        if ($status === 'live' && ! $startedAt) {
            $startedAt = now();
        } elseif ($status === 'ended' && ! $endedAt) {
            $endedAt = now();
        }

        $liveStream->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'platform' => $platform,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'status' => $status,
            'is_active' => $serveToPublic,
            'is_featured' => $serveToPublic && $serveOnMainLive,
            'is_sod' => $isSod,
            'sod_price' => $isSod ? (float) ($validated['sod_price'] ?? 0) : 0,
            'sod_currency' => strtoupper((string) ($validated['sod_currency'] ?? 'KES')),
            'serve_on_main_live' => $serveOnMainLive,
            'donations_enabled' => $isSod ? false : $request->boolean('donations_enabled', false),
            'donation_goal' => $validated['donation_goal'] ?? null,
            'donation_call_to_action' => $validated['donation_call_to_action'] ?? $liveStream->donation_call_to_action,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);

        return redirect()->route('admin.streams.index')
            ->with('success', "Live stream '{$liveStream->title}' updated successfully.");
    }

    /**
     * Toggle whether this stream is the single active broadcast served to the public.
     */
    public function toggleStatus(LiveStream $liveStream): RedirectResponse
    {
        $isCurrentlyServingPublic = $liveStream->is_active && $liveStream->status === 'live';

        if ($isCurrentlyServingPublic) {
            $liveStream->update([
                'status' => 'ended',
                'is_active' => false,
                'is_featured' => false,
                'ended_at' => now(),
            ]);

            return redirect()->route('admin.streams.index')
                ->with('success', "Stream '{$liveStream->title}' has been taken off the public site.");
        }

        LiveStream::activateOnly($liveStream);

        return redirect()->route('admin.streams.index')
            ->with('success', "Stream '{$liveStream->title}' is now the exclusive LIVE stream served to the public.");
    }

    /**
     * Explicitly set a stream as the single active broadcast served to the public.
     */
    public function activatePublic(LiveStream $liveStream): RedirectResponse
    {
        LiveStream::activateOnly($liveStream);

        return redirect()->route('admin.streams.index')
            ->with('success', "Stream '{$liveStream->title}' is now the exclusive LIVE stream served to the public.");
    }

    /**
     * Toggle whether donations are enabled for a specific live stream.
     */
    public function toggleDonations(LiveStream $liveStream): RedirectResponse
    {
        $liveStream->update([
            'donations_enabled' => ! $liveStream->donations_enabled,
        ]);

        $state = $liveStream->donations_enabled ? 'activated' : 'disabled';

        return redirect()->route('admin.streams.index')
            ->with('success', "Donations {$state} for live stream '{$liveStream->title}'.");
    }

    /**
     * Toggle global live stream donation module on or off.
     */
    public function toggleGlobalDonations(): RedirectResponse
    {
        $current = (bool) SiteSetting::get('live_donations_enabled', true);
        $next = ! $current;

        SiteSetting::set('live_donations_enabled', $next, 'boolean', 'editorial', 'Enable viewer donations on live streams');

        $label = $next ? 'activated' : 'disabled';

        return redirect()->back()
            ->with('success', "Global Live Stream Donation Module has been {$label}.");
    }

    /**
     * Delete a live stream.
     */
    public function destroy(LiveStream $liveStream): RedirectResponse
    {
        $title = $liveStream->title;
        $liveStream->delete();

        return redirect()->route('admin.streams.index')
            ->with('success', "Live stream '{$title}' deleted.");
    }
}
