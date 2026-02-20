<x-filament-panels::page>
    @if(!$isEligibleToVote)
        {{-- Ineligible --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">Voting Not Available</h3>
                        <p class="text-red-100 mt-1">You are not eligible to vote at this time.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Reason:</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $ineligibilityReason }}</p>
                    </div>
                </div>
            </div>

            @if(!empty($memberInfo))
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Member Information</h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $memberInfo['name'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Code</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $memberInfo['code'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Branch</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $memberInfo['branch'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">MIGS Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $memberInfo['is_migs'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $memberInfo['is_migs'] ? 'MIGS Member' : 'Not MIGS Member' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Share Amount</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $memberInfo['share_amount'] > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        ₱{{ number_format($memberInfo['share_amount'], 2) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Process Type</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $memberInfo['process_type'] === 'Updating and Voting' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                        {{ $memberInfo['process_type'] ?? 'Not Set' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            <div class="bg-blue-50 dark:bg-blue-950 rounded-lg p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Need Help?</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            If you believe this is an error, please contact your branch administrator or the main office for assistance.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    @elseif($hasAlreadyVoted)
        {{-- Already voted — show submitted votes --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold">Vote Successfully Submitted</h3>
                        <p class="text-green-100 mt-1">Thank you for participating! Here are your votes:</p>
                        @if($controlNumber)
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm font-semibold">Control Number: {{ $controlNumber }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @foreach($positions as $position)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $position['title'] }}</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($position['candidates'] as $candidate)
                            <div class="relative rounded-lg border-2 border-green-500 bg-green-50 dark:bg-green-950">
                                <div class="absolute top-3 right-3">
                                    <div class="bg-green-500 rounded-full p-1">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="p-4 flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        @if(!empty($candidate['profile_image_url']))
                                            <img src="{{ $candidate['profile_image_url'] }}" alt="{{ $candidate['full_name'] }}"
                                                class="w-20 h-20 rounded-full object-cover border-2 border-green-500">
                                        @else
                                            <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-2 border-green-500">
                                                <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $candidate['full_name'] }}</h4>
                                        @if(!empty($candidate['background_profile']))
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $candidate['background_profile'] }}</p>
                                        @endif
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Your Vote
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="bg-blue-50 dark:bg-blue-950 rounded-lg p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Your vote has been recorded</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            Changes are no longer possible after submission.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- Active ballot --}}
        @php
            $totalSelected  = $this->getTotalSelectedVotes();
            $totalAvailable = $this->getTotalAvailableVotes();
            $progressPct    = $totalAvailable > 0 ? round(($totalSelected / $totalAvailable) * 100) : 0;
        @endphp

        <div class="space-y-6">
            {{-- Progress banner --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold">Your Voting Progress</h3>
                        <p class="text-primary-100 mt-1">{{ $totalSelected }} of {{ $totalAvailable }} votes cast</p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold">{{ $progressPct }}%</div>
                        <div class="text-sm text-primary-100">Complete</div>
                    </div>
                </div>
            </div>

            {{-- Positions --}}
            @forelse($positions as $position)
                @php
                    $voteCount   = $this->getVoteCount($position['id']);
                    $vacantCount = $position['vacant_count'];
                    $isFull      = $voteCount === $vacantCount;
                @endphp

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $position['title'] }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Vote for up to {{ $vacantCount }} candidate(s)</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $isFull ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $voteCount }} / {{ $vacantCount }} selected
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if(count($position['candidates']) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($position['candidates'] as $candidate)
                                    @php $isSelected = $this->isSelected($position['id'], $candidate['id']); @endphp

                                    <div wire:click="toggleVote('{{ $position['id'] }}', '{{ $candidate['id'] }}')"
                                        class="relative cursor-pointer rounded-lg border-2 transition-all duration-200
                                            {{ $isSelected
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950 shadow-md'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow' }}">

                                        @if($isSelected)
                                            <div class="absolute top-3 right-3 z-10">
                                                <div class="bg-primary-500 rounded-full p-1">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="p-4 flex items-start gap-4">
                                            <div class="flex-shrink-0">
                                                @if(!empty($candidate['profile_image_url']))
                                                    <img src="{{ $candidate['profile_image_url'] }}" alt="{{ $candidate['full_name'] }}"
                                                        class="w-20 h-20 rounded-full object-cover border-2
                                                            {{ $isSelected ? 'border-primary-500' : 'border-gray-200 dark:border-gray-700' }}">
                                                @else
                                                    <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-2
                                                        {{ $isSelected ? 'border-primary-500' : 'border-gray-300 dark:border-gray-600' }}">
                                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $candidate['full_name'] }}</h4>
                                                @if(!empty($candidate['background_profile']))
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $candidate['background_profile'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No candidates available for this position.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Active Positions</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">There are currently no positions available for voting.</p>
                </div>
            @endforelse

            {{-- Submit --}}
            @if(count($positions) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ready to submit your votes?</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">You must vote for at least one candidate before submitting.</p>
                        </div>
                        <button wire:click="submitVotes" type="button"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg wire:loading.remove wire:target="submitVotes" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg wire:loading wire:target="submitVotes" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span wire:loading.remove wire:target="submitVotes">Submit Vote</span>
                            <span wire:loading wire:target="submitVotes">Submitting...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
