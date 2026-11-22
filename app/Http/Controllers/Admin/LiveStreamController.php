<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
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
            ->withCount(['donations', 'completedDonations'])
            ->withSum('completedDonations', 'amount')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->get();

        $liveCount = $streams->where('status', 'live')->where('is_active', true)->count();
        $scheduledCount = $streams->where('status', 'scheduled')->count();
        $totalDonationsRaised = (float) Donation::completed()->sum('amount');
        $globalDonationsEnabled = (bool) SiteSetting::get('live_donations_enabled', true);
        $activeGateways = PaymentGateway::active()->get();

        return view('admin.streams.index', compact(
            'streams',
            'liveCount',
            'scheduledCount',
            'totalDonationsRaised',
            'globalDonationsEnabled',
            'activeGateways'
        ));
    }

    /**
     * Store a new live stream supporting YouTube, Facebook, Twitch, or custom embed.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:live_streams,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'platform' => ['required', 'string', 'in:youtube,facebook,twitch,custom'],
            'stream_url' => ['required', 'string', 'max:500'],
            'embed_url' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:scheduled,live,ended'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
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

        $embedUrl = ! empty($validated['embed_url'])
            ? $validated['embed_url']
            : LiveStream::resolveEmbedUrl($validated['platform'], $validated['stream_url'], $request->getHost());

        $isFeatured = $request->boolean('is_featured', false);
        if ($isFeatured) {
            LiveStream::where('is_featured', true)->update(['is_featured' => false]);
        }

        LiveStream::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'platform' => $validated['platform'],
            'stream_url' => $validated['stream_url'],
            'embed_url' => $embedUrl,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $isFeatured,
            'donations_enabled' => $request->boolean('donations_enabled', true),
            'donation_goal' => $validated['donation_goal'] ?? null,
            'donation_call_to_action' => $validated['donation_call_to_action'] ?? 'Support Kisii TV Live Coverage',
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'started_at' => $validated['status'] === 'live' ? now() : null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.streams.index')
            ->with('success', "Live stream '{$validated['title']}' created and configured for ".ucfirst($validated['platform']).'.');
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
            'stream_url' => ['required', 'string', 'max:500'],
            'embed_url' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:scheduled,live,ended'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'donations_enabled' => ['nullable', 'boolean'],
            'donation_goal' => ['nullable', 'numeric', 'min:0'],
            'donation_call_to_action' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $embedUrl = ! empty($validated['embed_url'])
            ? $validated['embed_url']
            : LiveStream::resolveEmbedUrl($validated['platform'], $validated['stream_url'], $request->getHost());

        $isFeatured = $request->boolean('is_featured', false);
        if ($isFeatured) {
            LiveStream::where('id', '!=', $liveStream->id)->where('is_featured', true)->update(['is_featured' => false]);
        }

        $startedAt = $liveStream->started_at;
        $endedAt = $liveStream->ended_at;

        if ($validated['status'] === 'live' && ! $startedAt) {
            $startedAt = now();
        } elseif ($validated['status'] === 'ended' && ! $endedAt) {
            $endedAt = now();
        }

        $liveStream->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'platform' => $validated['platform'],
            'stream_url' => $validated['stream_url'],
            'embed_url' => $embedUrl,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'status' => $validated['status'],
            'is_active' => $request->boolean('is_active', false),
            'is_featured' => $isFeatured,
            'donations_enabled' => $request->boolean('donations_enabled', false),
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
     * Toggle live status between 'live' and 'ended'.
     */
    public function toggleStatus(LiveStream $liveStream): RedirectResponse
    {
        $newStatus = $liveStream->status === 'live' ? 'ended' : 'live';

        $liveStream->update([
            'status' => $newStatus,
            'started_at' => $newStatus === 'live' ? ($liveStream->started_at ?? now()) : $liveStream->started_at,
            'ended_at' => $newStatus === 'ended' ? now() : null,
        ]);

        $label = $newStatus === 'live' ? 'ON AIR (Live)' : 'Ended';

        return redirect()->route('admin.streams.index')
            ->with('success', "Stream '{$liveStream->title}' is now {$label}.");
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
