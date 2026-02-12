<x-filament-panels::page>

    {{-- Data carrier: JS reads this, Livewire never touches it --}}
    <div id="vote-results-data"
         data-results="{{ htmlspecialchars(json_encode($results), ENT_QUOTES, 'UTF-8') }}"
         style="display:none">
    </div>

    {{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">

        <div class="relative w-full sm:max-w-xs">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search candidate..."
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 py-2 pl-9 pr-3 text-sm
                       text-gray-900 dark:text-gray-100
                       focus:outline-none focus:ring-2 focus:ring-primary-500 transition"
            />
        </div>

        <select
            wire:model.live="filterPosition"
            class="rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 py-2 px-3 text-sm
                   text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-primary-500 transition"
        >
            <option value="">All Positions</option>
            @foreach ($positions as $pos)
                <option value="{{ $pos->id }}">{{ $pos->title }}</option>
            @endforeach
        </select>

    </div>

    {{-- ── One table per position ───────────────────────────────────────── --}}
    @forelse ($results as $position)

        <div class="mb-8 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
             id="position-{{ $position['id'] }}">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 bg-primary-600 dark:bg-primary-700">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                                 M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                                 m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-white tracking-wide">{{ $position['title'] }}</h3>
                </div>
                <div class="flex items-center gap-4 text-xs text-white/80">
                    <span><span class="font-semibold text-white">{{ $position['slots'] }}</span> slot{{ $position['slots'] != 1 ? 's' : '' }}</span>
                    <span><span class="font-semibold text-white">{{ $position['total_votes'] }}</span> total vote{{ $position['total_votes'] != 1 ? 's' : '' }}</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 w-10 text-center">#</th>
                            <th class="px-4 py-3">Candidate</th>
                            <th class="px-4 py-3 text-center">Total Votes</th>
                            <th class="px-4 py-3 text-center">Online</th>
                            <th class="px-4 py-3 text-center">On-site</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @forelse ($position['candidates'] as $i => $candidate)
                            @php
                                $isWinning = $i < $position['slots'] && $candidate['total_votes'] > 0;
                                $rowBg     = $isWinning
                                    ? 'bg-green-50/60 dark:bg-green-900/10'
                                    : ($i % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-gray-800/40');
                                $voteBadge = match(true) {
                                    $candidate['total_votes'] >= 100 => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    $candidate['total_votes'] >= 50  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    $candidate['total_votes'] >= 1   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                    default                          => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                };
                            @endphp

                            <tr class="{{ $rowBg }} hover:bg-primary-50/50 dark:hover:bg-primary-900/10 transition-colors">

                                <td class="px-4 py-3 text-center text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($candidate['image_url'])
                                            <img src="{{ $candidate['image_url'] }}"
                                                 alt="{{ $candidate['full_name'] }}"
                                                 class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 shadow-sm"/>
                                        @else
                                            <div class="w-9 h-9 rounded-full flex items-center justify-center
                                                        bg-primary-100 dark:bg-primary-900/50 ring-2 ring-white dark:ring-gray-700
                                                        text-primary-700 dark:text-primary-300 text-xs font-bold shadow-sm">
                                                {{ $candidate['initials'] }}
                                            </div>
                                        @endif
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $candidate['full_name'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2.25rem] px-2.5 py-0.5 rounded-full text-xs font-bold {{ $voteBadge }}">
                                        {{ $candidate['total_votes'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">
                                        {{ $candidate['online_votes'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{ $candidate['onsite_votes'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if ($isWinning)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Winning
                                        </span>
                                    @elseif ($candidate['total_votes'] === 0)
                                        <span class="text-xs text-gray-400">—</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            Trailing
                                        </span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">
                                    No candidates match your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if (count($position['candidates']) > 0)
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <td colspan="2" class="px-4 py-2 text-right">Totals</td>
                                <td class="px-4 py-2 text-center">{{ $position['total_votes'] }}</td>
                                <td class="px-4 py-2 text-center">{{ collect($position['candidates'])->sum('online_votes') }}</td>
                                <td class="px-4 py-2 text-center">{{ collect($position['candidates'])->sum('onsite_votes') }}</td>
                                <td class="px-4 py-2"></td>
                            </tr>
                        </tfoot>
                    @endif

                </table>
            </div>
        </div>

    @empty
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0
                         0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0
                         0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-sm font-medium">No results found</p>
            <p class="text-xs mt-1">No active positions or candidates configured yet.</p>
        </div>
    @endforelse

    {{-- Print CSS only — no script tags here --}}
    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header,
            .fi-footer, .fi-page-header,
            input, select { display: none !important; }
            body, .fi-main { background: white !important; }
            [id^="position-"] {
                border: 1px solid #e5e7eb !important;
                margin-bottom: 24px;
                page-break-inside: avoid;
            }
        }
    </style>

</x-filament-panels::page>
