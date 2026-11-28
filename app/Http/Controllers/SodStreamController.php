<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\StreamAccessPass;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SodStreamController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $gatewayService,
        protected SmsService $smsService
    ) {}

    /**
     * Display the SOD (Streaming On Demand) player or paywall gate.
     * Raw embed URLs are NEVER rendered in HTML unless this exact browser/device holds a valid paid pass.
     */
    public function show(Request $request, LiveStream $liveStream): View|RedirectResponse
    {
        if (! $liveStream->is_sod) {
            return redirect()->route('live.show', $liveStream->slug);
        }

        $isStaffPreview = auth()->check() && auth()->user()->canAccessAdmin();

        if (! $liveStream->is_active && ! $isStaffPreview) {
            abort(404);
        }

        $navCategories = Category::cachedNavCategories();
        $activeGateways = PaymentGateway::cachedActivePublic();

        $activePass = $this->resolveAuthorizedPass($request, $liveStream);
        $hasAccess = $activePass !== null || $isStaffPreview;

        if ($activePass) {
            $activePass->update(['last_watched_at' => now()]);
        }

        if ($hasAccess) {
            $liveStream->increment('viewer_count');
        }

        return view('live.sod', compact(
            'liveStream',
            'hasAccess',
            'activePass',
            'isStaffPreview',
            'activeGateways',
            'navCategories'
        ));
    }

    /**
     * Initiate SOD payment from the paywall modal (Paystack direct checkout or manual/test fallback).
     */
    public function purchase(Request $request, LiveStream $liveStream): RedirectResponse
    {
        abort_unless($liveStream->is_sod && $liveStream->is_active, 404);

        $validated = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'buyer_phone' => ['nullable', 'required_without:buyer_email', 'string', 'max:40'],
            'buyer_email' => ['nullable', 'required_without:buyer_phone', 'email', 'max:150'],
            'gateway_code' => ['nullable', 'string', 'max:50'],
        ], [
            'buyer_phone.required_without' => 'Enter your phone number or email address to unlock this stream.',
            'buyer_email.required_without' => 'Enter your phone number or email address to unlock this stream.',
        ]);

        $amount = max(0, (float) $liveStream->sod_price);
        $normalizedPhone = filled($validated['buyer_phone'] ?? null)
            ? $this->smsService->normalizePhone((string) $validated['buyer_phone'])
            : null;
        $buyerEmail = filled($validated['buyer_email'] ?? null)
            ? trim((string) $validated['buyer_email'])
            : null;
        $buyerName = filled($validated['buyer_name'] ?? null)
            ? trim((string) $validated['buyer_name'])
            : (auth()->user()?->name ?: 'SOD Viewer');

        $gatewayCode = $validated['gateway_code']
            ?: PaymentGateway::where('is_active', true)->orderBy('display_order')->value('code')
            ?: 'paystack';
        $gateway = PaymentGateway::where('code', $gatewayCode)->where('is_active', true)->first();

        $pass = StreamAccessPass::create([
            'live_stream_id' => $liveStream->id,
            'user_id' => auth()->id(),
            'payment_gateway_id' => $gateway?->id,
            'gateway_code' => $gateway?->code ?? $gatewayCode,
            'order_reference' => StreamAccessPass::generateOrderReference(),
            'buyer_name' => $buyerName,
            'buyer_email' => $buyerEmail,
            'buyer_phone' => $normalizedPhone,
            'amount' => $amount,
            'currency' => $liveStream->sod_currency ?: ($gateway?->currency ?: 'KES'),
            'status' => 'pending',
            'access_token' => StreamAccessPass::generateAccessToken(),
            'expires_at' => now()->addHours(24),
        ]);

        // If price is 0, unlock immediately bound to this device
        if ($amount <= 0) {
            return $this->bindPassToCurrentDeviceAndRedirect($request, $pass, 'FREE-SOD');
        }

        if (! $gateway) {
            return back()->withInput()->with('error', 'No active payment gateway is currently configured.');
        }

        // Direct hosted Paystack checkout
        if ($gateway->code === 'paystack') {
            $callbackUrl = route('sod.callback', [
                'gateway' => 'paystack',
                'order' => $pass->order_reference,
            ]);

            $init = $this->gatewayService->initializePaystackPayment(
                gateway: $gateway,
                amount: $amount,
                email: $buyerEmail ?: 'sod@kisiitv.co.ke',
                reference: $pass->order_reference,
                callbackUrl: $callbackUrl,
                metadata: [
                    'type' => 'sod_stream',
                    'live_stream_id' => $liveStream->id,
                    'stream_title' => $liveStream->title,
                    'buyer_phone' => $normalizedPhone,
                ]
            );

            if ($init['url']) {
                return redirect()->away($init['url']);
            }

            if ($init['error']) {
                return back()->withInput()->with('error', $init['error']);
            }
        }

        return redirect()->route('sod.checkout', $pass->order_reference);
    }

    /**
     * Fallback manual/test checkout page for SOD when gateway is not hosted-redirect.
     */
    public function checkout(StreamAccessPass $pass): View|RedirectResponse
    {
        $pass->load(['liveStream', 'paymentGateway']);

        if ($pass->isPaidAndActive()) {
            return redirect()->route('sod.show', $pass->liveStream->slug);
        }

        $navCategories = Category::cachedNavCategories();

        return view('live.sod-checkout', compact('pass', 'navCategories'));
    }

    /**
     * Verify Paystack/Flutterwave return callback, bind pass to buyer's device, and unlock playback.
     */
    public function callback(Request $request, string $gateway): RedirectResponse
    {
        $reference = (string) ($request->query('reference')
            ?? $request->query('trxref')
            ?? $request->query('tx_ref')
            ?? $request->query('order')
            ?? '');

        $pass = StreamAccessPass::with('liveStream')->where('order_reference', $reference)->first();

        if (! $pass) {
            return redirect()->route('live.index')->with('error', 'Stream pass order could not be found.');
        }

        if ($pass->status === 'revoked') {
            return redirect()->route('sod.show', $pass->liveStream->slug)
                ->with('error', 'This stream pass has been revoked by the administrator.');
        }

        // Prevent link-sharing replay if pass was already bound to another device
        if ($pass->isPaidAndActive() && $pass->device_fingerprint_hash !== null) {
            $cookieKey = $request->cookie(StreamAccessPass::deviceCookieName($pass->live_stream_id));
            if ($pass->matchesDeviceKey($cookieKey)) {
                return redirect()->route('sod.show', $pass->liveStream->slug);
            }

            return redirect()->route('sod.show', $pass->liveStream->slug)
                ->with('error', 'This stream pass is already locked to another device and cannot be shared. Please purchase your own pass to watch.');
        }

        $gw = PaymentGateway::where('code', $gateway)->first();
        $verified = false;

        if ($gw && $gateway === 'paystack') {
            $verified = $this->gatewayService->verifyPaystack($reference, $gw);
        } elseif ($gw && $gateway === 'flutterwave') {
            $txId = (string) ($request->query('transaction_id') ?? '');
            $verified = $txId !== '' && $this->gatewayService->verifyFlutterwave($txId, $gw);
        }

        if (! $verified) {
            return redirect()->route('sod.checkout', $pass->order_reference)
                ->with('error', 'Payment could not be verified yet. Reference: '.$pass->order_reference);
        }

        return $this->bindPassToCurrentDeviceAndRedirect($request, $pass, $reference);
    }

    /**
     * Confirm a manual/test SOD checkout and bind the pass to this exact device.
     */
    public function complete(Request $request, StreamAccessPass $pass): RedirectResponse
    {
        $pass->load('liveStream');

        if ($pass->status === 'revoked') {
            return redirect()->route('sod.show', $pass->liveStream->slug)
                ->with('error', 'This stream pass has been revoked by the administrator.');
        }

        if ($pass->isPaidAndActive() && $pass->device_fingerprint_hash !== null) {
            $cookieKey = $request->cookie(StreamAccessPass::deviceCookieName($pass->live_stream_id));
            if (! $pass->matchesDeviceKey($cookieKey)) {
                return redirect()->route('sod.show', $pass->liveStream->slug)
                    ->with('error', 'This stream pass is already bound to another device and cannot be shared.');
            }

            return redirect()->route('sod.show', $pass->liveStream->slug);
        }

        $validated = $request->validate([
            'external_reference' => ['nullable', 'string', 'max:120'],
        ]);

        return $this->bindPassToCurrentDeviceAndRedirect(
            $request,
            $pass,
            $validated['external_reference'] ?? ('SOD-'.strtoupper(substr(md5($pass->order_reference), 0, 8)))
        );
    }

    /**
     * Live heartbeat endpoint to verify the viewer's device pass is still authorized.
     */
    public function heartbeat(Request $request, LiveStream $liveStream): JsonResponse
    {
        if (auth()->check() && auth()->user()->canAccessAdmin()) {
            return response()->json(['authorized' => true]);
        }

        $pass = $this->resolveAuthorizedPass($request, $liveStream);

        if (! $pass) {
            return response()->json(['authorized' => false], 403);
        }

        $pass->update(['last_watched_at' => now()]);

        return response()->json(['authorized' => true]);
    }

    /**
     * Bind a paid pass to the current browser via an HttpOnly signed cookie + SHA-256 fingerprint.
     * Sharing the URL with another device will NOT work because the cookie never leaves this browser.
     */
    protected function bindPassToCurrentDeviceAndRedirect(Request $request, StreamAccessPass $pass, string $externalRef): RedirectResponse
    {
        $deviceKey = Str::random(64);
        $pass->update([
            'status' => 'paid',
            'paid_at' => $pass->paid_at ?? now(),
            'external_reference' => $externalRef,
            'device_fingerprint_hash' => hash('sha256', $deviceKey),
            'active_session_id' => $request->session()->getId(),
            'last_watched_at' => now(),
            'expires_at' => $pass->expires_at ?? now()->addHours(24),
        ]);

        $cookieName = StreamAccessPass::deviceCookieName($pass->live_stream_id);
        $cookie = Cookie::make(
            name: $cookieName,
            value: $deviceKey,
            minutes: 60 * 24,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        return redirect()->route('sod.show', $pass->liveStream->slug)
            ->withCookie($cookie)
            ->with('success', 'Payment confirmed! Your stream is unlocked on this device.');
    }

    /**
     * Verify whether the current request holds a valid, device-bound paid pass for this stream.
     */
    protected function resolveAuthorizedPass(Request $request, LiveStream $liveStream): ?StreamAccessPass
    {
        $cookieKey = $request->cookie(StreamAccessPass::deviceCookieName($liveStream->id));

        if (! is_string($cookieKey) || $cookieKey === '') {
            return null;
        }

        $hash = hash('sha256', $cookieKey);

        $pass = StreamAccessPass::where('live_stream_id', $liveStream->id)
            ->where('status', 'paid')
            ->where('device_fingerprint_hash', $hash)
            ->first();

        if (! $pass || ! $pass->isPaidAndActive()) {
            return null;
        }

        return $pass;
    }
}
