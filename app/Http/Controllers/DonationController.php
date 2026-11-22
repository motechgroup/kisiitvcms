<?php

namespace App\Http\Controllers;

use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Donation;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DonationController extends Controller
{
    /**
     * Display the public donation & supporter portal.
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $activeGateways = PaymentGateway::active()->get();
        $breakingNews = BreakingNews::active()->get();
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        return view('donations.index', compact('activeGateways', 'breakingNews', 'navCategories'));
    }

    /**
     * Initiate a new viewer donation via M-Pesa, Paystack, PayPal, or Flutterwave.
     */
    public function store(Request $request): RedirectResponse
    {
        PaymentGateway::ensureDefaultsExist();

        $validated = $request->validate([
            'live_stream_id' => ['nullable', 'exists:live_streams,id'],
            'gateway_code' => ['required', 'string', 'in:mpesa,paystack,paypal,flutterwave'],
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
            'donor_name' => ['required', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:30', 'required_if:gateway_code,mpesa'],
            'message' => ['nullable', 'string', 'max:500'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        if (! (bool) SiteSetting::get('live_donations_enabled', true)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Donations are currently disabled on the platform.');
        }

        $liveStream = null;
        if (! empty($validated['live_stream_id'])) {
            $liveStream = LiveStream::find($validated['live_stream_id']);
            if ($liveStream && ! $liveStream->areDonationsActive()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Donations are currently disabled for this live stream.');
            }
        }

        $gateway = PaymentGateway::where('code', $validated['gateway_code'])
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The selected payment method is currently disabled or unavailable.');
        }

        $reference = 'KTV-DON-'.strtoupper(Str::random(10));
        while (Donation::where('transaction_reference', $reference)->exists()) {
            $reference = 'KTV-DON-'.strtoupper(Str::random(10));
        }

        $donation = Donation::create([
            'live_stream_id' => $liveStream?->id,
            'payment_gateway_id' => $gateway->id,
            'gateway_code' => $gateway->code,
            'transaction_reference' => $reference,
            'donor_name' => $validated['donor_name'],
            'donor_email' => $validated['donor_email'] ?? null,
            'donor_phone' => $validated['donor_phone'] ?? null,
            'amount' => $validated['amount'],
            'currency' => $gateway->currency ?: 'KES',
            'message' => $validated['message'] ?? null,
            'is_anonymous' => $request->boolean('is_anonymous', false),
            'status' => 'pending',
            'metadata' => [
                'initiated_ip' => $request->ip(),
                'is_test_mode' => $gateway->is_test_mode,
            ],
        ]);

        return redirect()->route('donations.checkout', $donation->transaction_reference);
    }

    /**
     * Show the payment gateway checkout / confirmation screen.
     */
    public function checkout(Donation $donation): View
    {
        $donation->load(['liveStream', 'paymentGateway']);

        $breakingNews = BreakingNews::active()->get();
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        return view('live.checkout', compact('donation', 'breakingNews', 'navCategories'));
    }

    /**
     * Complete / confirm a donation payment callback from M-Pesa, Paystack, PayPal, or Flutterwave.
     */
    public function complete(Request $request, Donation $donation): RedirectResponse
    {
        $validated = $request->validate([
            'external_reference' => ['nullable', 'string', 'max:150'],
        ]);

        if ($donation->status !== 'completed') {
            $prefix = strtoupper($donation->gateway_code);
            $externalRef = ! empty($validated['external_reference'])
                ? $validated['external_reference']
                : $prefix.'-'.strtoupper(Str::random(8));

            $donation->update([
                'status' => 'completed',
                'external_reference' => $externalRef,
                'paid_at' => now(),
            ]);
        }

        if ($donation->liveStream) {
            return redirect()->route('live.show', $donation->liveStream->slug)
                ->with('success', "Thank you, {$donation->displayName()}! Your {$donation->currency} ".number_format((float) $donation->amount, 2).' contribution via '.strtoupper($donation->gateway_code)." (Ref: {$donation->transaction_reference}) has been received.");
        }

        return redirect()->route('donations.index')
            ->with('success', "Thank you, {$donation->displayName()}! Your {$donation->currency} ".number_format((float) $donation->amount, 2).' contribution to Kisii TV via '.strtoupper($donation->gateway_code)." (Ref: {$donation->transaction_reference}) has been received. Thank you for powering independent journalism!");
    }
}
