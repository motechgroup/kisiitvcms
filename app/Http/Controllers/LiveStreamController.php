<?php

namespace App\Http\Controllers;

use App\Models\AdPlacement;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LiveStreamController extends Controller
{
    /**
     * Display the single active public live broadcast and donation desk.
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $activeStream = LiveStream::active()
            ->where('status', 'live')
            ->orderByDesc('is_featured')
            ->latest('id')
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
     * Display a specific live stream by slug (only if it is the active public stream, or if viewed by admin).
     */
    public function show(LiveStream $liveStream): View|RedirectResponse
    {
        if (! $liveStream->is_active) {
            if (! auth()->check()) {
                return redirect()->route('live.index');
            }
        }

        PaymentGateway::ensureDefaultsExist();

        $liveStream->increment('viewer_count');

        return $this->renderStreamView($liveStream);
    }

    /**
     * Build the view payload for the live broadcast page (serving only the single chosen stream).
     */
    protected function renderStreamView(?LiveStream $activeStream): View
    {
        $breakingNews = BreakingNews::active()->get();

        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        $otherStreams = collect();

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
