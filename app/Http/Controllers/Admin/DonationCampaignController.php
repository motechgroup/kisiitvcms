<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationCampaignController extends Controller
{
    /**
     * Display a listing of donation campaigns in Admin.
     */
    public function index(Request $request): View
    {
        $query = DonationCampaign::with(['creator'])
            ->withCount(['completedDonations as total_donors'])
            ->withSum('completedDonations as total_raised', 'amount');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new donation campaign.
     */
    public function create(): View
    {
        return view('admin.campaigns.create');
    }

    /**
     * Store a newly created donation campaign in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'target_amount' => ['required', 'numeric', 'min:100', 'max:100000000'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'cover_image' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $slug = DonationCampaign::generateUniqueSlug($validated['title']);

        $campaign = DonationCampaign::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'target_amount' => $validated['target_amount'],
            'currency' => $validated['currency'] ?: 'KES',
            'cover_image' => $validated['cover_image'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.campaigns.index')
            ->with('success', "Campaign \"{$campaign->title}\" created successfully! You can now share its direct donation link: {$campaign->shareUrl()}");
    }

    /**
     * Display the specified campaign with detailed donation logs.
     */
    public function show(DonationCampaign $campaign): View
    {
        $campaign->load(['creator']);
        $donations = $campaign->donations()
            ->with('paymentGateway')
            ->latest()
            ->paginate(20);

        return view('admin.campaigns.show', compact('campaign', 'donations'));
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(DonationCampaign $campaign): View
    {
        return view('admin.campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(Request $request, DonationCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'target_amount' => ['required', 'numeric', 'min:100', 'max:100000000'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'cover_image' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $campaign->update([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'target_amount' => $validated['target_amount'],
            'currency' => $validated['currency'] ?: 'KES',
            'cover_image' => $validated['cover_image'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
        ]);

        return redirect()->route('admin.campaigns.index')
            ->with('success', "Campaign \"{$campaign->title}\" updated successfully.");
    }

    /**
     * Toggle the campaign active state.
     */
    public function toggleActive(DonationCampaign $campaign): RedirectResponse
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        $status = $campaign->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Campaign \"{$campaign->title}\" {$status}.");
    }

    /**
     * Toggle the campaign featured state.
     */
    public function toggleFeatured(DonationCampaign $campaign): RedirectResponse
    {
        $campaign->update(['is_featured' => ! $campaign->is_featured]);

        $status = $campaign->is_featured ? 'featured on portals' : 'unfeatured';

        return redirect()->back()
            ->with('success', "Campaign \"{$campaign->title}\" is now {$status}.");
    }

    /**
     * Remove the specified campaign from storage.
     */
    public function destroy(DonationCampaign $campaign): RedirectResponse
    {
        $title = $campaign->title;
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')
            ->with('success', "Campaign \"{$title}\" deleted.");
    }
}
