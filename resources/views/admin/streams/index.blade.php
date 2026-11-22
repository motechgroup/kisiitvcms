@extends('layouts.admin')

@section('title', 'Live Stream Studio & Broadcast Management')
@section('page_title', 'Live Stream Broadcast Studio (YouTube, Twitch & Facebook)')

@section('content')
<div class="space-y-8" x-data="{ createModalOpen: false, editModalOpen: false, activeStream: null }">

    <!-- Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900 font-headline">Live Streaming &amp; Multi-Platform Embed Control</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Embed live broadcasts from <strong>YouTube</strong>, <strong>Facebook Live</strong>, or <strong>Twitch</strong>, and activate or disable viewer donations per stream or globally.
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
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Streams Currently On Air</span>
            <span class="text-xl font-black text-[#d9252a] mt-1 block font-headline">{{ $liveCount }} LIVE</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Scheduled Broadcasts</span>
            <span class="text-xl font-black text-amber-600 mt-1 block font-headline">{{ $scheduledCount }}</span>
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
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-headline">
                Configured Live Streams (YouTube, Twitch &amp; Facebook)
            </h3>
            <a href="{{ route('live.index') }}" target="_blank" class="text-xs font-bold text-[#1b52bc] hover:underline">
                Open Public Live Studio &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Broadcast Title</th>
                        <th class="py-3 px-4">Platform</th>
                        <th class="py-3 px-4 text-center">Broadcast Status</th>
                        <th class="py-3 px-4 text-center">Live Donations</th>
                        <th class="py-3 px-4">Raised / Goal</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($streams as $stream)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    @if($stream->is_featured)
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[9px] font-black uppercase px-1.5 py-0.5 rounded">Featured</span>
                                    @endif
                                    <a href="{{ route('live.show', $stream->slug) }}" target="_blank" class="font-bold text-slate-900 hover:text-[#1b52bc] text-sm">
                                        {{ $stream->title }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-400 font-mono truncate max-w-md mt-0.5">
                                    {{ $stream->stream_url }}
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
                                <form method="POST" action="{{ route('admin.streams.toggle-status', $stream) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($stream->status === 'live')
                                        <button type="submit" title="Click to end broadcast"
                                            class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded bg-red-600 text-white text-[10px] font-black uppercase tracking-wider hover:bg-slate-800 transition cursor-pointer">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                            <span>ON AIR (LIVE)</span>
                                        </button>
                                    @elseif($stream->status === 'scheduled')
                                        <button type="submit" title="Click to go live now"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-wider hover:bg-red-600 hover:text-white transition cursor-pointer">
                                            <span>Scheduled (Go Live)</span>
                                        </button>
                                    @else
                                        <button type="submit" title="Click to reactivate live stream"
                                            class="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider hover:bg-red-600 hover:text-white transition cursor-pointer">
                                            <span>Ended (Go Live)</span>
                                        </button>
                                    @endif
                                </form>
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
                                @if($stream->donation_goal)
                                    <div class="text-[10px] text-slate-400">
                                        of KES {{ number_format((float) $stream->donation_goal, 0) }} goal
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('live.show', $stream->slug) }}" target="_blank" class="text-emerald-700 hover:underline font-semibold">
                                    Watch
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
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.streams.store') }}" class="p-6 space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Stream Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Kisii County Assembly Budget Special Live Coverage"
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Streaming Platform *</label>
                        <select name="platform" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                            <option value="youtube">YouTube Live</option>
                            <option value="facebook">Facebook Live</option>
                            <option value="twitch">Twitch</option>
                            <option value="custom">Custom Iframe URL</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Stream Status *</label>
                        <select name="status" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                            <option value="live">LIVE NOW (On Air)</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="ended">Ended / Replay</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Stream Watch / Channel URL *</label>
                    <input type="text" name="stream_url" required
                        placeholder="YouTube URL (https://youtube.com/watch?v=...), Facebook Video URL, or Twitch Channel URL"
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    <p class="text-[11px] text-slate-400 mt-1">Paste any YouTube watch/live link, Facebook video link, or Twitch channel/video link — the embed player URL is generated automatically.</p>
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Custom Embed URL Override (Optional)</label>
                    <input type="text" name="embed_url" placeholder="Leave blank to auto-generate from Stream URL"
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-700 mb-1">Broadcast Description</label>
                    <textarea name="description" rows="2" placeholder="Summary of the live broadcast..."
                        class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Donation Goal (KES, Optional)</label>
                        <input type="number" name="donation_goal" step="any" min="0" placeholder="e.g. 50000"
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Donation Call-To-Action Heading</label>
                        <input type="text" name="donation_call_to_action" value="Support Kisii TV Live Coverage"
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6 pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="donations_enabled" value="1" checked class="rounded text-emerald-600">
                        <span class="font-bold text-slate-800">Activate Donations for this Live Stream</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" checked class="rounded text-[#1b52bc]">
                        <span class="font-bold text-slate-800">Feature as Primary Stream</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#1b52bc]">
                        <span class="font-bold text-slate-800">Published / Active</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded bg-slate-100 text-slate-700 font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded bg-[#d9252a] hover:bg-red-700 text-white font-bold uppercase tracking-wider cursor-pointer">
                        Launch Live Stream
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
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Streaming Platform *</label>
                            <select name="platform" x-model="activeStream.platform" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                                <option value="youtube">YouTube Live</option>
                                <option value="facebook">Facebook Live</option>
                                <option value="twitch">Twitch</option>
                                <option value="custom">Custom Iframe URL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Stream Status *</label>
                            <select name="status" x-model="activeStream.status" required class="w-full border border-slate-300 rounded px-3 py-2 bg-white focus:outline-none focus:border-[#1b52bc]">
                                <option value="live">LIVE NOW (On Air)</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="ended">Ended / Replay</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Stream Watch / Channel URL *</label>
                        <input type="text" name="stream_url" x-model="activeStream.stream_url" required
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                    </div>

                    <div>
                        <label class="block font-bold uppercase text-slate-700 mb-1">Broadcast Description</label>
                        <textarea name="description" x-model="activeStream.description" rows="2"
                            class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Donation Goal (KES)</label>
                            <input type="number" name="donation_goal" x-model="activeStream.donation_goal" step="any" min="0"
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-slate-700 mb-1">Donation Call-To-Action Heading</label>
                            <input type="text" name="donation_call_to_action" x-model="activeStream.donation_call_to_action"
                                class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-[#1b52bc]">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="donations_enabled" value="1" :checked="activeStream.donations_enabled" class="rounded text-emerald-600">
                            <span class="font-bold text-slate-800">Activate Donations for this Live Stream</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" :checked="activeStream.is_featured" class="rounded text-[#1b52bc]">
                            <span class="font-bold text-slate-800">Feature as Primary Stream</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" :checked="activeStream.is_active" class="rounded text-[#1b52bc]">
                            <span class="font-bold text-slate-800">Published / Active</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded bg-slate-100 text-slate-700 font-bold">Cancel</button>
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
