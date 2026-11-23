<?php

namespace App\Http\Controllers;

use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use App\Services\MpesaService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    public function store(Request $request, MpesaService $mpesaService, PaymentGatewayService $gatewayService): RedirectResponse
    {
        PaymentGateway::ensureDefaultsExist();

        $validated = $request->validate([
            'live_stream_id' => ['nullable', 'exists:live_streams,id'],
            'campaign_id' => ['nullable', 'exists:donation_campaigns,id'],
            'gateway_code' => ['required', 'string', 'in:mpesa,paystack,paypal,flutterwave'],
            'amount' => ['required', 'numeric', 'min:10', 'max:10000000'],
            'donor_name' => ['nullable', 'string', 'max:120'],
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

        $campaign = null;
        if (! empty($validated['campaign_id'])) {
            $campaign = DonationCampaign::find($validated['campaign_id']);
            if ($campaign && ! $campaign->is_active) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'This donation campaign is currently closed.');
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

        $donorName = trim((string) ($validated['donor_name'] ?? ''));

        $donation = Donation::create([
            'live_stream_id' => $liveStream?->id,
            'campaign_id' => $campaign?->id,
            'payment_gateway_id' => $gateway->id,
            'gateway_code' => $gateway->code,
            'transaction_reference' => $reference,
            'donor_name' => $donorName !== '' ? $donorName : 'Kisii TV Supporter',
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

        if ($gateway->code === 'mpesa') {
            $stkResult = $mpesaService->initiateStkPush($donation, $gateway);

            return redirect()->route('donations.checkout', $donation->transaction_reference)
                ->with('stk_result', $stkResult);
        }

        if ($gateway->code === 'paystack') {
            $paystackUrl = $gatewayService->initializePaystack($donation, $gateway);
            if ($paystackUrl) {
                return redirect()->away($paystackUrl);
            }
        }

        if ($gateway->code === 'flutterwave') {
            $flutterwaveUrl = $gatewayService->initializeFlutterwave($donation, $gateway);
            if ($flutterwaveUrl) {
                return redirect()->away($flutterwaveUrl);
            }
        }

        return redirect()->route('donations.checkout', $donation->transaction_reference);
    }

    /**
     * Handle browser redirect callback from Paystack or Flutterwave hosted checkout.
     */
    public function callback(Request $request, string $gateway, PaymentGatewayService $gatewayService): RedirectResponse
    {
        $reference = $request->input('reference')
            ?? $request->input('trxref')
            ?? $request->input('tx_ref')
            ?? $request->input('donation');

        $donation = $reference ? Donation::where('transaction_reference', $reference)->first() : null;

        if (! $donation) {
            return redirect()->route('donations.index')
                ->with('error', 'Donation transaction reference could not be located.');
        }

        $paymentGateway = $donation->paymentGateway ?? PaymentGateway::where('code', $gateway)->first();
        $verified = false;
        $externalRef = null;

        if ($gateway === 'paystack' && $paymentGateway) {
            $verified = $gatewayService->verifyPaystack($donation->transaction_reference, $paymentGateway);
            $externalRef = $request->input('reference', $donation->transaction_reference);
        } elseif ($gateway === 'flutterwave' && $paymentGateway) {
            $txId = (string) $request->input('transaction_id', '');
            $status = $request->input('status');
            if ($status === 'successful' || $status === 'completed') {
                $verified = $txId !== '' ? $gatewayService->verifyFlutterwave($txId, $paymentGateway) : true;
                $externalRef = $txId !== '' ? $txId : $donation->transaction_reference;
            }
        }

        if (! $verified) {
            return redirect()->route('donations.checkout', $donation->transaction_reference)
                ->with('error', 'Payment was not completed or could not be verified. You can retry below.');
        }

        if ($donation->status !== 'completed') {
            $donation->update([
                'status' => 'completed',
                'external_reference' => $externalRef ?: strtoupper($gateway).'-'.strtoupper(Str::random(8)),
                'paid_at' => now(),
            ]);
        }

        return $this->redirectAfterCompletion($donation);
    }

    /**
     * Show the payment gateway checkout / confirmation screen.
     */
    public function checkout(Donation $donation): View
    {
        $donation->load(['liveStream', 'paymentGateway', 'campaign']);

        $breakingNews = BreakingNews::active()->get();
        $navCategories = Category::roots()
            ->menu()
            ->with(['children' => fn ($q) => $q->menu()])
            ->get();

        return view('live.checkout', compact('donation', 'breakingNews', 'navCategories'));
    }

    /**
     * Trigger an instant Safaricom M-Pesa STK Push prompt to the donor's phone.
     */
    public function initiateStkPush(Request $request, Donation $donation, MpesaService $mpesaService): JsonResponse
    {
        if ($donation->gateway_code !== 'mpesa') {
            return response()->json([
                'success' => false,
                'message' => 'STK push is only supported for M-Pesa donations.',
            ], 422);
        }

        if ($request->filled('phone')) {
            $donation->update(['donor_phone' => $request->input('phone')]);
        }

        $result = $mpesaService->initiateStkPush($donation);

        return response()->json($result);
    }

    /**
     * Check current donation payment status (useful for client polling while waiting for STK PIN or webhook).
     */
    public function status(Donation $donation): JsonResponse
    {
        return response()->json([
            'status' => $donation->status,
            'is_completed' => $donation->status === 'completed',
            'external_reference' => $donation->external_reference,
            'redirect_url' => $donation->campaign
                ? route('campaigns.show', $donation->campaign->slug)
                : ($donation->liveStream ? route('live.show', $donation->liveStream->slug) : route('donations.index')),
        ]);
    }

    /**
     * Webhook / IPN listener for automated gateway notifications (M-Pesa, Paystack, Flutterwave, PayPal).
     */
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        Log::info("Payment webhook received for [{$gateway}]", ['payload' => $request->all()]);

        if ($gateway === 'mpesa') {
            $callback = $request->input('Body.stkCallback');
            if ($callback) {
                $resultCode = $callback['ResultCode'] ?? -1;
                $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

                if ($resultCode === 0 && $checkoutRequestId) {
                    $donation = Donation::where('metadata->checkout_request_id', $checkoutRequestId)
                        ->where('status', '!=', 'completed')
                        ->first();

                    if ($donation) {
                        $receipt = null;
                        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
                            if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                                $receipt = $item['Value'] ?? null;
                            }
                        }

                        $donation->update([
                            'status' => 'completed',
                            'external_reference' => $receipt ?: 'MPESA-'.strtoupper(Str::random(8)),
                            'paid_at' => now(),
                        ]);
                    }
                }
            }
        } elseif ($gateway === 'paystack') {
            $event = $request->input('event');
            $reference = $request->input('data.reference');

            if ($event === 'charge.success' && $reference) {
                $donation = Donation::where('transaction_reference', $reference)
                    ->orWhere('external_reference', $reference)
                    ->first();

                if ($donation && $donation->status !== 'completed') {
                    $donation->update([
                        'status' => 'completed',
                        'external_reference' => $reference,
                        'paid_at' => now(),
                    ]);
                }
            }
        } elseif ($gateway === 'flutterwave') {
            $status = $request->input('data.status');
            $txRef = $request->input('data.tx_ref');

            if ($status === 'successful' && $txRef) {
                $donation = Donation::where('transaction_reference', $txRef)->first();
                if ($donation && $donation->status !== 'completed') {
                    $donation->update([
                        'status' => 'completed',
                        'external_reference' => $request->input('data.id', $txRef),
                        'paid_at' => now(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'received']);
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

        return $this->redirectAfterCompletion($donation);
    }

    /**
     * Redirect donor to the originating campaign, live stream, or general donation portal.
     */
    protected function redirectAfterCompletion(Donation $donation): RedirectResponse
    {
        if ($donation->campaign) {
            return redirect()->route('campaigns.show', $donation->campaign->slug)
                ->with('success', "Thank you, {$donation->displayName()}! Your {$donation->currency} ".number_format((float) $donation->amount, 2)." contribution to the \"{$donation->campaign->title}\" campaign via ".strtoupper($donation->gateway_code)." (Ref: {$donation->transaction_reference}) has been received. Thank you for empowering our community mission!");
        }

        if ($donation->liveStream) {
            return redirect()->route('live.show', $donation->liveStream->slug)
                ->with('success', "Thank you, {$donation->displayName()}! Your {$donation->currency} ".number_format((float) $donation->amount, 2).' contribution via '.strtoupper($donation->gateway_code)." (Ref: {$donation->transaction_reference}) has been received.");
        }

        return redirect()->route('donations.index')
            ->with('success', "Thank you, {$donation->displayName()}! Your {$donation->currency} ".number_format((float) $donation->amount, 2).' contribution to Kisii TV via '.strtoupper($donation->gateway_code)." (Ref: {$donation->transaction_reference}) has been received. Thank you for powering independent journalism!");
    }
}
