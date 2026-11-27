@extends('layouts.admin')

@section('title', $isEdit ? 'Edit Event' : 'Create Event')
@section('page_title', $isEdit ? 'Edit Event: '.$event->title : 'Create New Event')

@section('content')
@php
    $initialTiers = old('ticket_types');
    if (! is_array($initialTiers) || $initialTiers === []) {
        if ($isEdit && $event->ticketTypes->isNotEmpty()) {
            $initialTiers = $event->ticketTypes->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description ?? '',
                'price' => (string) $t->price,
                'quantity_available' => $t->quantity_available !== null ? (string) $t->quantity_available : '',
            ])->values()->all();
        } else {
            $initialTiers = [
                ['id' => '', 'name' => 'Regular', 'description' => 'Standard event admission', 'price' => '500', 'quantity_available' => '200'],
                ['id' => '', 'name' => 'VIP', 'description' => 'Front-row seating & VIP lounge access', 'price' => '2000', 'quantity_available' => '50'],
            ];
        }
    }
@endphp

<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.events.index') }}" class="text-xs font-bold text-slate-600 hover:text-[#1b52bc]">
            &larr; Back to Events Manager
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-xs space-y-1">
            <div class="font-bold uppercase tracking-wider">Please fix the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}"
        enctype="multipart/form-data"
        x-data="{ tiers: @js($initialTiers) }"
        class="bg-white border border-slate-200 rounded-xl shadow-2xs p-6 space-y-6 text-xs">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block font-bold uppercase text-slate-700 mb-1">Event Title *</label>
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required
                    placeholder="e.g. Kisii Cultural & Music Festival 2026"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block font-bold uppercase text-slate-700 mb-1">Venue *</label>
                <input type="text" name="venue" value="{{ old('venue', $event->venue) }}" required
                    placeholder="e.g. Gusii Stadium"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block font-bold uppercase text-slate-700 mb-1">City / County</label>
                <input type="text" name="city" value="{{ old('city', $event->city) }}"
                    placeholder="e.g. Kisii Town"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block font-bold uppercase text-slate-700 mb-1">Starts At *</label>
                <input type="datetime-local" name="starts_at"
                    value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div>
                <label class="block font-bold uppercase text-slate-700 mb-1">Ends At (Optional)</label>
                <input type="datetime-local" name="ends_at"
                    value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
                <label class="block font-bold uppercase text-slate-700">Event Cover Banner (Upload)</label>
                @if($isEdit && $event->cover_image)
                    <img src="{{ $event->coverImageUrl() }}" alt="Current Cover Banner" class="w-full h-24 object-cover rounded-lg border border-slate-200">
                @endif
                <input type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp"
                    class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1b52bc] file:text-white hover:file:bg-blue-800 cursor-pointer">
                <p class="text-[10px] text-slate-500">Landscape banner (16:9 recommended, max 5MB). Used on hero &amp; header banner.</p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
                <label class="block font-bold uppercase text-slate-700">Event Flyer / Poster (Upload)</label>
                @if($isEdit && $event->flyer_image)
                    <img src="{{ $event->flyerImageUrl() }}" alt="Current Event Flyer" class="h-24 w-auto object-contain rounded-lg border border-slate-200 bg-white">
                @endif
                <input type="file" name="flyer_image_file" accept="image/jpeg,image/png,image/webp"
                    class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                <p class="text-[10px] text-slate-500">Promotional poster/flyer (portrait or square, max 5MB). Displayed on event page.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block font-bold uppercase text-slate-700 mb-1">Short Summary</label>
                <input type="text" name="summary" value="{{ old('summary', $event->summary) }}"
                    placeholder="One-line highlight shown on the events listing card"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
            </div>

            <div class="md:col-span-2">
                <label class="block font-bold uppercase text-slate-700 mb-1">Full Event Description</label>
                <textarea name="description" rows="4"
                    placeholder="Schedule, guest speakers, performers, dress code, parking info..."
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#1b52bc]">{{ old('description', $event->description) }}</textarea>
            </div>
        </div>

        <!-- Ticket Tiers Builder -->
        <div class="border-t border-slate-200 pt-5 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Ticket Tiers &amp; Pricing</h3>
                    <p class="text-[11px] text-slate-500">Add one or more ticket categories (set price to 0 for free RSVP tickets).</p>
                </div>
                <button type="button"
                    @click="tiers.push({ id: '', name: '', description: '', price: '500', quantity_available: '' })"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg cursor-pointer">
                    + Add Tier
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(tier, idx) in tiers" :key="idx">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3.5 items-end">
                        <input type="hidden" :name="`ticket_types[${idx}][id]`" x-model="tier.id">

                        <div class="sm:col-span-3">
                            <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Tier Name *</label>
                            <input type="text" :name="`ticket_types[${idx}][name]`" x-model="tier.name" required
                                placeholder="e.g. Regular / VIP"
                                class="w-full border border-slate-300 bg-white rounded-lg px-2.5 py-2">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Price (KES) *</label>
                            <input type="number" step="any" min="0" :name="`ticket_types[${idx}][price]`" x-model="tier.price" required
                                class="w-full border border-slate-300 bg-white rounded-lg px-2.5 py-2 font-mono font-bold">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Capacity</label>
                            <input type="number" min="1" :name="`ticket_types[${idx}][quantity_available]`" x-model="tier.quantity_available"
                                placeholder="Unlimited"
                                class="w-full border border-slate-300 bg-white rounded-lg px-2.5 py-2 font-mono">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Perks / Note</label>
                            <input type="text" :name="`ticket_types[${idx}][description]`" x-model="tier.description"
                                placeholder="Optional perks description"
                                class="w-full border border-slate-300 bg-white rounded-lg px-2.5 py-2">
                        </div>

                        <div class="sm:col-span-1 text-right">
                            <button type="button" x-show="tiers.length > 1" @click="tiers.splice(idx, 1)"
                                class="px-2.5 py-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold cursor-pointer">
                                &times;
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Flags & Save -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-200">
            <div class="flex items-center gap-5">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }} class="rounded text-emerald-600">
                    <span class="font-bold text-slate-700">Published on Public Events Page</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $event->is_featured) ? 'checked' : '' }} class="rounded text-[#1b52bc]">
                    <span class="font-bold text-slate-700">Featured Hero Event</span>
                </label>
            </div>

            <button type="submit"
                class="px-6 py-2.5 bg-[#1b52bc] hover:bg-blue-800 text-white font-black uppercase tracking-wider rounded-lg shadow transition cursor-pointer">
                {{ $isEdit ? 'Save Event Changes' : 'Publish Event' }}
            </button>
        </div>
    </form>
</div>
@endsection
