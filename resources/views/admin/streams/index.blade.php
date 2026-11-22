@extends('layouts.admin')

@section('title', 'Live Stream Studio & Broadcast Management')
@section('page_title', 'Live Stream Broadcast Studio (YouTube, Twitch & Facebook)')

@section('content')
<div class="space-y-8" x-data="{ createModalOpen: false, editModalOpen: false, createPlatform: 'youtube', activeStream: null }">

    <!-- Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Live Streaming &amp; Public Broadcast Selector</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Configure <strong>YouTube</strong>, <strong>Facebook Live</strong>, or <strong>Twitch (JavaScript Video Player Embed)</strong> streams. Only <strong>one stream at a time</strong> is served to the public.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Global Live Donations Master Switch -->
            <form method="POST" action="{{ route('admin.streams.toggle-global-donations') }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="px-3.5 py-2 text-xs font-bold uppercase tracking-wider rounded border transition cursor-pointer flex items-center space-x-1.5 {{ $globalDonationsEnabled ? 'bg-emerald-50 border-emerald-300 text-emerald-800 hover:bg-emerald-100' : 'bg-slate-100 border-slate-300 text-slate-600 hover:bg-slate-200' }}">
                    <span class="w-2 h-2 rounded-full {{ $globalDonationsEnabled ? 'bg-emerald-600' : 'bg-slate-400' }}"></span>
                    <span>Global Live Donations: {{ $globalDonationsEnabled ? 'ACTIVE' : 'DISABLED' }}</span>
                </button>
            </form>

            <a href="{{ route('admin.payment-gateways.index') }}"
                class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded transition">
                Payment Gateways ({{ $activeGateways->count() }})
            </a>

            <button type="button" @click="createModalOpen = true"
                class="px-4 py-2 bg-[#d9252a] hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded transition flex items-center space-x-1.5 shadow cursor-pointer">
                <span>+</span>
                <span>New Live Stream</span>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Public Stream On Air</span>
            <span class="text-xl font-black text-[#d9252a] mt-1 block font-headline">{{ $liveCount }} ACTIVE</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Configured Streams</span>
            <span class="text-xl font-black text-slate-900 mt-1 block font-headline">{{ $streams->count() }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Viewer Donations Raised</span>
            <span class="text-xl font-black text-emerald-600 mt-1 block font-headline">KES {{ number_format($totalDonationsRaised, 2) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Enabled Payment Methods</span>
            <span class="text-xs font-bold text-[#1b52bc] mt-2 block uppercase">
                {{ $activeGateways->pluck('code')->map(fn($c) => strtoupper($c))->join(' • ') ?: 'None Active' }}
            </span>
        </div>
    </div>

    <!-- Configured Streams Table -->
    <div class="bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                    Configured Live Streams — Select Which Single Stream Is Served to the Public
                </h3>
                <p class="text-[11px] text-slate-500">Clicking "Serve to Public" on any stream automatically makes it the exclusive public broadcast.</p>
            </div>
            <a href="{{ route('live.index') }}" target="_blank" class="text-xs font-bold text-[#1b52bc] hover:underline shrink-0">
                Open Public Live Studio &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Broadcast Title</th>
                        <th class="py-3 px-4">Platform</th>
                        <th class="py-3 px-4 text-center">Public Broadcast Selection</th>
                        <th class="py-3 px-4 text-center">Live Donations</th>
                        <th class="py-3 px-4">Donations Raised</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($streams as $stream)
                        @php
                            $isPublicStream = $stream->is_active && $stream->status === 'live';
                        @endphp
                        <tr class="{{ $isPublicStream ? 'bg-red-50/40' : 'hover:bg-slate-50' }} transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    @if($isPublicStream)
                                        <span class="bg-[#d9252a] text-white text-[9px] font-black uppercase px-2 py-0.5 rounded flex items-center space-x-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                            <span>PUBLIC STREAM</span>
                                        </span>
                                    @endif
                                    <span class="font-bold text-slate-900 text-sm">
                                        {{ $stream->title }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 font-mono truncate max-w-md mt-0.5">
                                    {{ Str::limit($stream->embed_url ?: $stream->stream_url, 80) }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded text-[10px] font-black uppercase tracking-wider
                                    {{ $stream->platform === 'youtube' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $stream->platform === 'facebook' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $stream->platform === 'twitch' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $stream->platform === 'custom' ? 'bg-slate-100 text-slate-700' : '' }}">
                                    {{ ucfirst($stream->platform) }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @if($isPublicStream)
                                    <div class="inline-flex items-center space-x-2">
                                        <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                            <span>Serving to Public</span>
                                        </span>
                                        <form method="POST" action="{{ route('admin.streams.toggle-status', $stream) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Take this stream off the public site"
                                                class="px-2 py-1 rounded bg-slate-200 hover:bg-red-600 text-slate-700 hover:text-white text-[10px] font-bold uppercase transition cursor-pointer">
                                                Stop
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('admin.streams.activate-public', $stream) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Make this the single stream served to the public"
                                            class="inline-flex items-center space-x-1 px-3 py-1.5 rounded bg-[#1b52bc] hover:bg-blue-800 text-white text-[10px] font-black uppercase tracking-wider transition shadow-sm cursor-pointer">
                                            <span>Serve to Public</span>
                                        </button>
                                    </form>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('admin.streams.toggle-donations', $stream) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($stream->donations_enabled)
                                        <button type="submit" title="Click to disable donations for this stream"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase hover:bg-red-100 hover:text-red-800 transition cursor-pointer">
                                            <span>✓ Donate Active</span>
                                        </button>
                                    @else
                                        <button type="submit" title="Click to enable donations for this stream"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-slate-100 text-slate-500 text-[10px] font-black uppercase hover:bg-emerald-100 hover:text-emerald-800 transition cursor-pointer">
                                            <span>Donate Disabled</span>
                                        </button>
                                    @endif
                                </form>
                            </td>

                            <td class="py-3.5 px-4 font-mono">
                                <div class="font-bold text-emerald-700">
                                    KES {{ number_format((float) ($stream->completed_donations_sum_amount ?? 0), 0) }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('live.index') }}" target="_blank" class="text-emerald-700 hover:underline font-semibold">
                                    Preview
                                </a>
                                <button type="button"
                                    @click="activeStream = {{ Js::from($stream) }}; editModalOpen = true"
                                    class="text-blue-600 hover:underline font-semibold cursor-pointer">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.streams.destroy', $stream) }}" class="inline" onsubmit="return confirm('Delete this live stream?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline font-semibold cursor-pointer">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No live streams configured yet. Click "+ New Live Stream" to add a YouTube, Facebook, or Twitch broadcast.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CREATE LIVE STREAM MODAL -->
    <div x-show="createModalOpen" x-cloak style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
        x-transition>
        <div @click.outside="createModalOpen = false" class="bg-white w-full max-w-xl rounded-lg shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0a1c3d] text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase tracking-wider font-headline">Add New Live Stream Broadcast</h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-white text-lg cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.streams.store') }}" class="p-6 space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Stream Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Kisii County Assembly Budget Special Live Coverage"
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Streaming Platform *</label>
                    <select name="platform" x-model="createPlatform" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                        <option value="youtube">YouTube Live</option>
                        <option value="twitch">Twitch (JavaScript or Iframe Embed)</option>
                        <option value="facebook">Facebook Live</option>
                        <option value="custom">Custom Embed URL</option>
                    </select>
                </div>

                <!-- TWITCH SETTINGS: JAVASCRIPT OR IFRAME EMBED CODE (NO CHANNEL URL REQUIRED) -->
                <template x-if="createPlatform === 'twitch'">
                    <div class="bg-purple-50/60 border border-purple-200 rounded-lg p-4 space-y-2">
                        <label class="block font-bold uppercase text-purple-900">Twitch Embed Code (JavaScript or Iframe) *</label>
                        <textarea name="embed_url" rows="6" required
                            placeholder='Paste either Twitch Iframe code:&#10;<iframe src="https://player.twitch.tv/?channel=strogo&parent=www.example.com" frameborder="0" allowfullscreen="true" scrolling="no" height="378" width="620"></iframe>&#10;&#10;OR Twitch JavaScript code:&#10;<script src="https://player.twitch.tv/js/embed/v1.js"></script>...'
                            class="w-full border border-purple-300 rounded px-3 py-2 font-mono text-[11px] bg-white focus:outline-none focus:border-purple-600"></textarea>
                        <p class="text-[11px] text-purple-700">
                            Paste either a Twitch <code>&lt;iframe src="https://player.twitch.tv/..."&gt;&lt;/iframe&gt;</code> tag or the Twitch JavaScript embed snippet. The <code>parent</code> domain and responsive 16:9 sizing are configured automatically.
                        </p>
                    </div>
                </template>

                <!-- YOUTUBE / FACEBOOK / CUSTOM SETTINGS -->
                <template x-if="createPlatform !== 'twitch'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Stream Watch / Video URL *</label>
                            <input type="text" name="stream_url" required
                                placeholder="Paste YouTube watch/live URL or Facebook Live video URL..."
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                            <p class="text-[11px] text-slate-400 mt-1">Paste any YouTube watch/live link or Facebook video link — the responsive embed player is configured automatically.</p>
                        </div>
                    </div>
                </template>

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Broadcast Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Summary of the live broadcast..."
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <label class="block font-bold uppercase text-slate-700 mb-1">Donation Modal Heading</label>
                    <input type="text" name="donation_call_to_action" value="Support Kisii TV Live Coverage"
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#d9252a]">
                        <span class="font-bold text-slate-800">Serve this stream to Public now (replaces current public stream)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="donations_enabled" value="1" checked class="rounded text-emerald-600">
                        <span class="font-bold text-slate-800">Activate Donations</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded bg-[#d9252a] hover:bg-red-700 text-white font-bold uppercase tracking-wider cursor-pointer">
                        Save &amp; Launch Stream
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT LIVE STREAM MODAL -->
    <div x-show="editModalOpen" x-cloak style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
        x-transition>
        <div @click.outside="editModalOpen = false" class="bg-white w-full max-w-xl rounded-lg shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0a1c3d] text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase tracking-wider font-headline">Edit Live Stream Broadcast</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white text-lg cursor-pointer">&times;</button>
            </div>

            <template x-if="activeStream">
                <form method="POST" :action="'/admin/streams/' + activeStream.slug" class="p-6 space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Stream Title *</label>
                        <input type="text" name="title" x-model="activeStream.title" required
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    </div>

                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Streaming Platform *</label>
                        <select name="platform" x-model="activeStream.platform" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                            <option value="youtube">YouTube Live</option>
                            <option value="twitch">Twitch (JavaScript or Iframe Embed)</option>
                            <option value="facebook">Facebook Live</option>
                            <option value="custom">Custom Embed URL</option>
                        </select>
                    </div>

                    <!-- TWITCH SETTINGS: JAVASCRIPT OR IFRAME EMBED CODE -->
                    <template x-if="activeStream.platform === 'twitch'">
                        <div class="bg-purple-50/60 border border-purple-200 rounded-lg p-4 space-y-2">
                            <label class="block font-bold uppercase text-purple-900">Twitch Embed Code (JavaScript or Iframe) *</label>
                            <textarea name="embed_url" x-model="activeStream.embed_url" rows="6" required
                                class="w-full border border-purple-300 rounded px-3 py-2 font-mono text-[11px] bg-white focus:outline-none focus:border-purple-600"></textarea>
                            <p class="text-[11px] text-purple-700">
                                Paste either a Twitch <code>&lt;iframe src="https://player.twitch.tv/..."&gt;&lt;/iframe&gt;</code> tag or the Twitch JavaScript embed snippet. The <code>parent</code> domain is updated automatically.
                            </p>
                        </div>
                    </template>

                    <!-- YOUTUBE / FACEBOOK / CUSTOM SETTINGS -->
                    <template x-if="activeStream.platform !== 'twitch'">
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Stream Watch / Video URL *</label>
                            <input type="text" name="stream_url" x-model="activeStream.stream_url" required
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                        </div>
                    </template>

                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Broadcast Description (Optional)</label>
                        <textarea name="description" x-model="activeStream.description" rows="2"
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]"></textarea>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="block font-bold uppercase text-slate-700 mb-1">Donation Modal Heading</label>
                        <input type="text" name="donation_call_to_action" x-model="activeStream.donation_call_to_action"
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" :checked="activeStream.is_active" class="rounded text-[#d9252a]">
                            <span class="font-bold text-slate-800">Serve this stream to Public (replaces current public stream)</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="donations_enabled" value="1" :checked="activeStream.donations_enabled" class="rounded text-emerald-600">
                            <span class="font-bold text-slate-800">Activate Donations</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded bg-[#1b52bc] hover:bg-blue-800 text-white font-bold uppercase tracking-wider cursor-pointer">
                            Save Changes
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
