@props([
    'gateways',
    'liveStreamId' => null,
    'campaignId' => null,
    'currency' => 'KES',
    'presets' => [200, 500, 1000, 5000],
    'defaultAmount' => '500',
    'amountModel' => 'amount',
    'gatewayModel' => 'gateway',
    'submitLabel' => 'Proceed to Donate',
    'cancelAction' => null,
])

<form method="POST" action="{{ route('donations.store') }}" class="space-y-4 text-left" x-data="{ showOptional: false }">
    @csrf

    @if($liveStreamId)
        <input type="hidden" name="live_stream_id" value="{{ $liveStreamId }}">
    @endif

    @if($campaignId)
        <input type="hidden" name="campaign_id" value="{{ $campaignId }}">
    @endif

    <!-- 1. Amount Selection -->
    <div>
        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-2">
            1. Select or Enter Amount ({{ $currency }})
        </label>
        <div class="grid grid-cols-4 gap-2 mb-2">
            @foreach($presets as $preset)
                <button type="button"
                    @click="{{ $amountModel }} = '{{ $preset }}'"
                    :class="{{ $amountModel }} == '{{ $preset }}' ? 'bg-emerald-600 text-white border-emerald-400 shadow ring-1 ring-emerald-400' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-slate-600'"
                    class="py-2 px-1 text-xs font-black rounded-lg border transition text-center cursor-pointer">
                    {{ number_format($preset) }}
                </button>
            @endforeach
        </div>
        <div class="relative">
            <span class="absolute left-3.5 top-2.5 text-slate-500 font-mono text-xs font-bold">{{ $currency }}</span>
            <input type="number" name="amount" x-model="{{ $amountModel }}" min="10" step="any" required
                placeholder="Enter custom amount..."
                class="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 rounded-lg pl-12 pr-3 py-2.5 text-xs font-bold text-white focus:outline-none">
        </div>
    </div>

    <!-- 2. Payment Method -->
    <div>
        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-300 mb-2">
            2. Choose Payment Method
        </label>
        <div class="grid grid-cols-2 gap-2">
            @foreach($gateways as $gw)
                <label @click="{{ $gatewayModel }} = '{{ $gw->code }}'"
                    :class="{{ $gatewayModel }} === '{{ $gw->code }}' ? 'border-emerald-400 bg-emerald-950/40 text-white shadow' : 'border-slate-800 bg-slate-950/60 text-slate-300 hover:border-slate-600'"
                    class="border-2 rounded-xl p-2.5 cursor-pointer transition flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="{{ $gatewayModel }}" class="sr-only">
                        <span class="text-xs font-black uppercase tracking-wide">
                            {{ $gw->code === 'mpesa' ? 'M-Pesa' : ucfirst($gw->code) }}
                        </span>
                        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-emerald-300">
                            {{ $gw->currency }}
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 line-clamp-1">
                        {{ $gw->provider_label ?? $gw->name }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- 3. Streamlined Gateway Input -->
    <div x-show="{{ $gatewayModel }} === 'mpesa'" x-cloak class="bg-emerald-950/20 border border-emerald-500/30 rounded-xl p-3.5 space-y-1.5">
        <label class="block text-[11px] font-black uppercase tracking-wider text-emerald-400">
            3. Enter M-Pesa Phone Number *
        </label>
        <input type="tel" name="donor_phone" value="{{ old('donor_phone') }}"
            :required="{{ $gatewayModel }} === 'mpesa'"
            placeholder="e.g. 0712345678"
            class="w-full bg-slate-950 border border-emerald-500/60 focus:border-emerald-400 rounded-lg px-3.5 py-2.5 text-sm font-mono font-bold text-white focus:outline-none">
        <p class="text-[10px] text-slate-400">
            Instant STK Push prompt will be sent straight to your phone. No extra details needed.
        </p>
    </div>

    <div x-show="{{ $gatewayModel }} !== 'mpesa'" x-cloak class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 text-[11px] text-slate-300">
        <span x-show="{{ $gatewayModel }} === 'paystack'">You will be redirected directly to Paystack's secure checkout page (Card, Apple Pay, M-Pesa).</span>
        <span x-show="{{ $gatewayModel }} === 'flutterwave'">You will be redirected directly to Flutterwave's secure checkout page (Card, Mobile Money, Bank).</span>
        <span x-show="{{ $gatewayModel }} === 'paypal'">You will be taken directly to PayPal's international checkout.</span>
    </div>

    <!-- Optional Details Accordion -->
    <div class="pt-1">
        <button type="button" @click="showOptional = !showOptional"
            class="text-[11px] font-bold text-slate-400 hover:text-emerald-300 transition flex items-center space-x-1 cursor-pointer">
            <span x-text="showOptional ? '− Hide optional details' : '+ Add my name, email, or message (optional)'"></span>
        </button>

        <div x-show="showOptional" x-cloak class="mt-3 space-y-2.5 p-3 rounded-xl bg-slate-950/60 border border-slate-800">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Your Name (Optional)</label>
                <input type="text" name="donor_name" value="{{ old('donor_name', auth()->user()?->name) }}"
                    placeholder="e.g. Kisii TV Supporter"
                    class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email for Receipt (Optional)</label>
                <input type="email" name="donor_email" value="{{ old('donor_email', auth()->user()?->email) }}"
                    placeholder="you@example.com"
                    class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Message (Optional)</label>
                <textarea name="message" rows="2" placeholder="Leave a quick note for Kisii TV..."
                    class="w-full bg-slate-900 border border-slate-700 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">{{ old('message') }}</textarea>
            </div>

            <label class="flex items-center space-x-2 text-xs text-slate-400 cursor-pointer">
                <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                    class="rounded bg-slate-900 border-slate-700 text-emerald-600 focus:ring-0">
                <span>Keep my contribution anonymous</span>
            </label>
        </div>
    </div>

    <!-- Submit Action -->
    <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-800">
        @if($cancelAction)
            <button type="button" @click="{{ $cancelAction }}"
                class="px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer">
                Close
            </button>
        @endif

        <button type="submit"
            class="{{ $cancelAction ? '' : 'w-full' }} py-3 px-5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-lg transition cursor-pointer flex items-center justify-center space-x-2">
            <span x-show="{{ $gatewayModel }} === 'mpesa'">Send M-Pesa STK Prompt &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'paystack'" x-cloak>Continue to Paystack Checkout &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'flutterwave'" x-cloak>Continue to Flutterwave Checkout &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'paypal'" x-cloak>Continue to PayPal &rarr;</span>
        </button>
    </div>
</form>
