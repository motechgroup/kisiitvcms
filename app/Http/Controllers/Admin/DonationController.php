<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationController extends Controller
{
    /**
     * Display the donations ledger, gateway breakdown, and live stream contributions.
     */
    public function index(Request $request): View
    {
        PaymentGateway::ensureDefaultsExist();

        $query = Donation::with(['liveStream', 'paymentGateway'])->latest();

        if ($request->filled('gateway')) {
            $query->where('gateway_code', $request->string('gateway')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('live_stream_id')) {
            $query->where('live_stream_id', $request->integer('live_stream_id'));
        }

        $donations = $query->paginate(20)->withQueryString();

        $totalRaised = (float) Donation::completed()->sum('amount');
        $completedCount = Donation::completed()->count();
        $pendingCount = Donation::pending()->count();

        $gateways = PaymentGateway::orderBy('display_order')->get();
        $streams = LiveStream::orderByDesc('id')->get();
        $globalDonationsEnabled = (bool) SiteSetting::get('live_donations_enabled', true);

        return view('admin.donations.index', compact(
            'donations',
            'totalRaised',
            'completedCount',
            'pendingCount',
            'gateways',
            'streams',
            'globalDonationsEnabled'
        ));
    }

    /**
     * Update a donation status (e.g. verify pending payment or mark failed).
     */
    public function updateStatus(Request $request, Donation $donation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed,failed,cancelled'],
            'external_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $donation->update([
            'status' => $validated['status'],
            'external_reference' => $validated['external_reference'] ?? $donation->external_reference,
            'paid_at' => $validated['status'] === 'completed' ? ($donation->paid_at ?? now()) : $donation->paid_at,
        ]);

        return redirect()->back()
            ->with('success', "Donation {$donation->transaction_reference} status updated to ".strtoupper($validated['status']).'.');
    }

    /**
     * Delete a donation record.
     */
    public function destroy(Donation $donation): RedirectResponse
    {
        $ref = $donation->transaction_reference;
        $donation->delete();

        return redirect()->route('admin.donations.index')
            ->with('success', "Donation record {$ref} deleted.");
    }
}
