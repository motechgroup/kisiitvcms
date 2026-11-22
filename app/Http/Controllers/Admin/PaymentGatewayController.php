<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    /**
     * Display the payment gateways configuration panel (M-Pesa, Paystack, PayPal, Flutterwave).
     */
    public function index(): View
    {
        PaymentGateway::ensureDefaultsExist();

        $gateways = PaymentGateway::withCount([
            'donations',
            'donations as completed_donations_count' => fn ($q) => $q->where('status', 'completed'),
        ])
            ->withSum(['donations as completed_donations_sum' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->orderBy('display_order')
            ->get();

        $globalDonationsEnabled = (bool) SiteSetting::get('live_donations_enabled', true);

        return view('admin.payment-gateways.index', compact('gateways', 'globalDonationsEnabled'));
    }

    /**
     * Update a payment gateway's API credentials, mode, and instructions.
     */
    public function update(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'provider_label' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'max:10'],
            'public_key' => ['nullable', 'string', 'max:1000'],
            'secret_key' => ['nullable', 'string', 'max:1000'],
            'passkey_or_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'shortcode_or_merchant_id' => ['nullable', 'string', 'max:150'],
            'account_reference' => ['nullable', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'is_test_mode' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'provider_label' => $validated['provider_label'] ?? $paymentGateway->provider_label,
            'description' => $validated['description'] ?? $paymentGateway->description,
            'currency' => strtoupper($validated['currency']),
            'public_key' => $validated['public_key'] ?? null,
            'shortcode_or_merchant_id' => $validated['shortcode_or_merchant_id'] ?? null,
            'account_reference' => $validated['account_reference'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => $request->boolean('is_active', false),
            'is_test_mode' => $request->boolean('is_test_mode', false),
        ];

        if ($request->filled('secret_key')) {
            $updateData['secret_key'] = $validated['secret_key'];
        }

        if ($request->filled('passkey_or_webhook_secret')) {
            $updateData['passkey_or_webhook_secret'] = $validated['passkey_or_webhook_secret'];
        }

        $paymentGateway->update($updateData);

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "Payment gateway '{$paymentGateway->name}' configuration saved.");
    }

    /**
     * Toggle a payment gateway active or inactive.
     */
    public function toggleActive(PaymentGateway $paymentGateway): RedirectResponse
    {
        $paymentGateway->update([
            'is_active' => ! $paymentGateway->is_active,
        ]);

        $state = $paymentGateway->is_active ? 'enabled' : 'disabled';

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "Payment gateway '{$paymentGateway->name}' has been {$state}.");
    }

    /**
     * Toggle a payment gateway between Test/Sandbox and Live production mode.
     */
    public function toggleMode(PaymentGateway $paymentGateway): RedirectResponse
    {
        $paymentGateway->update([
            'is_test_mode' => ! $paymentGateway->is_test_mode,
        ]);

        $mode = $paymentGateway->is_test_mode ? 'Sandbox / Test Mode' : 'Live Production Mode';

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "Payment gateway '{$paymentGateway->name}' switched to {$mode}.");
    }
}
