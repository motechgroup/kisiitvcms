<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use Illuminate\View\View;

class LiveStreamController extends Controller
{
    /**
     * Display the primary live broadcast studio and donation desk.
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $activeStream = LiveStream::active()
            ->where('status', 'live')
            ->orderByDesc('is_featured')
            ->latest('started_at')
            ->first();

        if (! $activeStream) {
            $activeStream = LiveStream::active()
                ->orderByDesc('is_featured')
                ->latest('id')
                ->first();
        }

        return $this->renderStreamView($activeStream);
    }

    /**
     * Display a specific live stream by slug.
     */
    public function show(LiveStream $liveStream): View
    {
        if (! $liveStream->is_active) {
            abort_unless(auth()->check(), 404);
        }

        PaymentGateway::ensureDefaultsExist();

        $liveStream->increment('viewer_count');

        return $this->renderStreamView($liveStream);
    }

    /**
     * Build the view payload for the live broadcast page.
     */
    protected function renderStreamView(?LiveStream $activeStream): View
    {
        $breakingNews = BreakingNews::active()->get();

        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        $otherStreams = LiveStream::active()
            ->when($activeStream, fn ($q) => $q->where('id', '!=', $activeStream->id))
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->latest('id')
            ->take(8)
            ->get();

        $activeGateways = PaymentGateway::active()->get();

        $recentDonations = Donation::completed()
            ->when($activeStream, fn ($q) => $q->where('live_stream_id', $activeStream->id))
            ->latest('paid_at')
            ->take(10)
            ->get();

        $headerAd = AdPlacement::where('slug', 'header-leaderboard')->first()?->activeAdvertisement();

        return view('live.index', compact(
            'activeStream',
            'otherStreams',
            'activeGateways',
            'recentDonations',
            'breakingNews',
            'navCategories',
            'headerAd'
        ));
    }
}
