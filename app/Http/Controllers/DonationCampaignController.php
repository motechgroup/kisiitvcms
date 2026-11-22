<?php

namespace App\Http\Controllers;

use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\DonationCampaign;
use App\Models\PaymentGateway;
use Illuminate\View\View;

class DonationCampaignController extends Controller
{
    /**
     * Display a listing of active donation campaigns.
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $campaigns = DonationCampaign::active()
            ->withCount(['completedDonations as total_donors'])
            ->withSum('completedDonations as total_raised', 'amount')
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(12);

        $breakingNews = BreakingNews::active()->get();
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        return view('campaigns.index', compact('campaigns', 'breakingNews', 'navCategories'));
    }

    /**
     * Display a single donation campaign with its live progress and direct donation checkout.
     */
    public function show(DonationCampaign $campaign): View
    {
        PaymentGateway::ensureDefaultsExist();

        $campaign->load(['creator']);
        $recentDonations = $campaign->completedDonations()
            ->latest('paid_at')
            ->take(8)
            ->get();

        $activeGateways = PaymentGateway::active()->get();
        $breakingNews = BreakingNews::active()->get();
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        return view('campaigns.show', compact('campaign', 'recentDonations', 'activeGateways', 'breakingNews', 'navCategories'));
    }
}
