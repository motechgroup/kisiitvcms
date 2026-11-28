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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Display SOD subscribers and finance report for a specific SOD live stream link.
     */
    public function sodReport(Request $request, LiveStream $liveStream): View
    {
        $statusFilter = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $allPasses = $liveStream->accessPasses()->get();

        $paidPasses = $allPasses->where('status', 'paid');
        $pendingPasses = $allPasses->where('status', 'pending');
        $revokedPasses = $allPasses->where('status', 'revoked');

        $grossPaidRevenue = (float) $paidPasses->sum('amount');
        $pendingAmount = (float) $pendingPasses->sum('amount');
        $revokedAmount = (float) $revokedPasses->sum('amount');
        $activeSubscribersCount = $paidPasses->filter(fn (StreamAccessPass $pass): bool => $pass->isPaidAndActive())->count();
        $totalAttempts = $allPasses->count();
        $conversionRate = $totalAttempts > 0 ? round(($paidPasses->count() / $totalAttempts) * 100, 1) : 0.0;

        $gatewayBreakdown = $allPasses
            ->groupBy(fn (StreamAccessPass $pass): string => strtoupper((string) ($pass->gateway_code ?: 'DIRECT')))
            ->map(function ($group, string $gateway): array {
                $paid = $group->where('status', 'paid');

                return [
                    'gateway' => $gateway,
                    'total_count' => $group->count(),
                    'paid_count' => $paid->count(),
                    'pending_count' => $group->where('status', 'pending')->count(),
                    'revoked_count' => $group->where('status', 'revoked')->count(),
                    'paid_revenue' => (float) $paid->sum('amount'),
                ];
            })
            ->sortByDesc('paid_revenue')
            ->values();

        $dailyRevenue = $paidPasses
            ->groupBy(fn (StreamAccessPass $pass): string => ($pass->paid_at ?? $pass->created_at)?->format('Y-m-d') ?? 'Unknown')
            ->map(fn ($group, string $date): array => [
                'date' => $date,
                'passes_count' => $group->count(),
                'revenue' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('date')
            ->values();

        $passesQuery = $liveStream->accessPasses()
            ->with(['user', 'paymentGateway'])
            ->latest('id');

        if ($statusFilter === 'paid') {
            $passesQuery->where('status', 'paid');
        } elseif ($statusFilter === 'pending') {
            $passesQuery->where('status', 'pending');
        } elseif ($statusFilter === 'revoked') {
            $passesQuery->where('status', 'revoked');
        } elseif ($statusFilter === 'expired') {
            $passesQuery->where('status', 'paid')->whereNotNull('expires_at')->where('expires_at', '<', now());
        }

        if ($search !== '') {
            $passesQuery->where(function ($query) use ($search): void {
                $query->where('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_phone', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%")
                    ->orWhere('order_reference', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%");
            });
        }

        $passes = $passesQuery->paginate(25)->withQueryString();

        return view('admin.streams.sod-report', compact(
            'liveStream',
            'passes',
            'statusFilter',
            'search',
            'grossPaidRevenue',
            'pendingAmount',
            'revokedAmount',
            'activeSubscribersCount',
            'totalAttempts',
            'conversionRate',
            'paidPasses',
            'pendingPasses',
            'revokedPasses',
            'gatewayBreakdown',
            'dailyRevenue'
        ));
    }

    /**
     * Revoke or enable (restore) an SOD subscriber pass.
     */
    public function updateSodPassStatus(Request $request, LiveStream $liveStream, StreamAccessPass $pass): RedirectResponse
    {
        abort_unless($pass->live_stream_id === $liveStream->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:paid,revoked'],
        ]);

        if ($validated['status'] === 'revoked') {
            $pass->update([
                'status' => 'revoked',
            ]);

            return redirect()->back()
                ->with('success', "Access revoked for subscriber {$pass->order_reference}.");
        }

        $expiresAt = ($pass->expires_at && $pass->expires_at->isFuture())
            ? $pass->expires_at
            : now()->addHours(24);

        $pass->update([
            'status' => 'paid',
            'paid_at' => $pass->paid_at ?? now(),
            'expires_at' => $expiresAt,
        ]);

        return redirect()->back()
            ->with('success', "Access enabled for subscriber {$pass->order_reference}.");
    }

    /**
     * Export SOD subscribers and finance ledger for a given SOD stream as CSV.
     */
    public function exportSodReport(LiveStream $liveStream): StreamedResponse
    {
        $filename = 'sod-finance-report-'.$liveStream->slug.'-'.now()->format('Ymd-His').'.csv';
        $passes = $liveStream->accessPasses()->latest('id')->get();

        return response()->streamDownload(function () use ($passes): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Order Reference',
                'External Reference',
                'Subscriber Name',
                'Phone',
                'Email',
                'Gateway',
                'Currency',
                'Amount',
                'Status',
                'Device Bound',
                'Paid At',
                'Last Watched At',
                'Expires At',
                'Created At',
            ]);

            foreach ($passes as $pass) {
                fputcsv($handle, [
                    $pass->order_reference,
                    $pass->external_reference ?? '',
                    $pass->buyer_name ?? '',
                    $pass->buyer_phone ?? '',
                    $pass->buyer_email ?? '',
                    strtoupper((string) $pass->gateway_code),
                    $pass->currency,
                    number_format((float) $pass->amount, 2, '.', ''),
                    $pass->status,
                    $pass->device_fingerprint_hash ? 'Yes' : 'No',
                    $pass->paid_at?->toDateTimeString() ?? '',
                    $pass->last_watched_at?->toDateTimeString() ?? '',
                    $pass->expires_at?->toDateTimeString() ?? '',
                    $pass->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
