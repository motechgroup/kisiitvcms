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

<form method="POST" action="{{ route('donations.store') }}" class="space-y-5 text-left" x-data="{ showOptional: false }">
    @csrf

    @if($liveStreamId)
        <input type="hidden" name="live_stream_id" value="{{ $liveStreamId }}">
    @endif

    @if($campaignId)
        <input type="hidden" name="campaign_id" value="{{ $campaignId }}">
    @endif

    <!-- 1. Amount Selection -->
    <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">
            1. Select Amount ({{ $currency }})
        </label>
        <div class="grid grid-cols-4 gap-2 mb-2.5">
            @foreach($presets as $preset)
                <button type="button"
                    @click="{{ $amountModel }} = '{{ $preset }}'"
                    :class="{{ $amountModel }} == '{{ $preset }}'
                        ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-md shadow-emerald-500/20'
                        : 'bg-slate-950/80 text-slate-200 border-slate-800 hover:border-slate-600 hover:bg-slate-900'"
                    class="py-2.5 px-2 text-xs font-extrabold rounded-xl border transition text-center cursor-pointer">
                    {{ number_format($preset) }}
                </button>
            @endforeach
        </div>
        <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-mono text-xs font-bold">{{ $currency }}</span>
            <input type="number" name="amount" x-model="{{ $amountModel }}" min="10" step="any" required
                placeholder="Enter custom amount..."
                class="w-full bg-slate-950/90 border border-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/40 rounded-xl pl-12 pr-3.5 py-2.5 text-sm font-bold text-white transition focus:outline-none">
        </div>
    </div>

    <!-- 2. Payment Method -->
    <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">
            2. Choose Payment Method
        </label>
        <div class="grid grid-cols-2 gap-3">
            @foreach($gateways as $gw)
                @php
                    $methodTagline = match ($gw->code) {
                        'mpesa' => 'Mobile Money',
                        'paystack' => 'Card, Apple Pay & More',
                        'flutterwave' => 'Card, Mobile Money & Bank',
                        'paypal' => 'Cards & PayPal Balance',
                        default => $gw->currency,
                    };
                @endphp
                <label @click="{{ $gatewayModel }} = '{{ $gw->code }}'"
                    :class="{{ $gatewayModel }} === '{{ $gw->code }}'
                        ? 'border-emerald-400 ring-2 ring-emerald-400/40 shadow-lg shadow-emerald-950/40 bg-slate-900'
                        : 'border-slate-800 bg-slate-950/70 opacity-85 hover:opacity-100 hover:border-slate-600'"
                    class="relative border-2 rounded-2xl overflow-hidden cursor-pointer transition flex flex-col">
                    <input type="radio" name="gateway_code" value="{{ $gw->code }}" x-model="{{ $gatewayModel }}" class="sr-only">

                    <div class="h-16 sm:h-20 w-full flex items-center justify-center overflow-hidden bg-slate-950">
                        @if($gwLogo = $gw->logoUrl())
                            <img src="{{ $gwLogo }}" alt="{{ $gw->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="p-3 text-center">
                                <span class="text-sm font-black uppercase tracking-wide text-white block">
                                    {{ ucfirst($gw->code) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="px-2.5 py-2 bg-slate-950/95 border-t border-slate-800/80 text-center">
                        <span class="text-[11px] font-semibold tracking-tight text-slate-200 block truncate">
                            {{ $methodTagline }}
                        </span>
                    </div>

                    <!-- Selected Checkmark Badge -->
                    <span x-show="{{ $gatewayModel }} === '{{ $gw->code }}'" x-cloak
                        class="absolute top-2 right-2 w-5 h-5 rounded-full bg-emerald-400 text-slate-950 flex items-center justify-center shadow-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- 3. Streamlined Gateway Input -->
    <div x-show="{{ $gatewayModel }} === 'mpesa'" x-cloak class="bg-emerald-950/25 border border-emerald-500/30 rounded-xl p-3.5 space-y-2">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-emerald-400">
            M-Pesa Phone Number *
        </label>
        <input type="tel" name="donor_phone" value="{{ old('donor_phone') }}"
            :required="{{ $gatewayModel }} === 'mpesa'"
            placeholder="e.g. 0712345678"
            class="w-full bg-slate-950 border border-emerald-500/50 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/40 rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold text-white transition focus:outline-none">
    </div>

    <!-- Optional Details Accordion -->
    <div>
        <button type="button" @click="showOptional = !showOptional"
            class="text-xs font-medium text-slate-400 hover:text-emerald-300 transition flex items-center gap-1.5 cursor-pointer">
            <span x-text="showOptional ? '− Hide optional details' : '+ Add name, email, or note (optional)'"></span>
        </button>

        <div x-show="showOptional" x-cloak class="mt-3 space-y-2.5 p-3.5 rounded-xl bg-slate-950/70 border border-slate-800">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Your Name (Optional)</label>
                <input type="text" name="donor_name" value="{{ old('donor_name', auth()->user()?->name) }}"
                    placeholder="e.g. Kisii TV Supporter"
                    class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email for Receipt (Optional)</label>
                <input type="email" name="donor_email" value="{{ old('donor_email', auth()->user()?->email) }}"
                    placeholder="you@example.com"
                    class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Message (Optional)</label>
                <textarea name="message" rows="2" placeholder="Leave a quick note for Kisii TV..."
                    class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-white focus:outline-none">{{ old('message') }}</textarea>
            </div>

            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
                <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                    class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                <span>Keep my contribution anonymous</span>
            </label>
        </div>
    </div>

    <!-- Submit Action -->
    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800/80">
        @if($cancelAction)
            <button type="button" @click="{{ $cancelAction }}"
                class="px-4 py-3 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer">
                Close
            </button>
        @endif

        <button type="submit"
            class="{{ $cancelAction ? 'flex-1' : 'w-full' }} py-3 px-5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-500/20 transition cursor-pointer flex items-center justify-center gap-2">
            <span x-show="{{ $gatewayModel }} === 'mpesa'">Pay with M-Pesa &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'paystack'" x-cloak>Continue to Paystack &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'flutterwave'" x-cloak>Continue to Flutterwave &rarr;</span>
            <span x-show="{{ $gatewayModel }} === 'paypal'" x-cloak>Continue to PayPal &rarr;</span>
        </button>
    </div>
</form>
