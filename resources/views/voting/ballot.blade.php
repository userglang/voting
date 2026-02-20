@extends('layouts.app')

@section('title', 'Cast Your Vote - Voting System')

@push('styles')
<style>
    /* Smooth transitions for all interactive elements */
    .candidate-card {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .candidate-card:hover:not(.border-blue-600) {
        transform: translateY(-2px);
    }

    /* Better focus indicators for accessibility */
    .candidate-card:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Vote Progress Banner --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-4 sm:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex-1">
                <h3 class="text-xl sm:text-2xl font-bold">Your Voting Progress</h3>
                <p class="text-blue-100 mt-1 text-sm sm:text-base">
                    <span id="total-selected" class="font-semibold">0</span> of
                    <span id="total-available">{{ $positions->sum('vacant_count') }}</span> votes cast
                </p>
                <p class="text-xs sm:text-sm text-blue-200 mt-2">
                    Voting as: <strong>{{ Str::upper($member->full_name) }}</strong> ({{ $member->code }})
                </p>
            </div>
            <div class="text-left sm:text-right">
                <div class="text-4xl sm:text-5xl font-bold" id="progress-percentage">0%</div>
                <div class="text-xs sm:text-sm text-blue-100 mt-1">Complete</div>
                <div class="mt-3 bg-white/20 rounded-lg px-3 py-2 inline-block">
                    <p class="text-xs text-blue-100">Session Time</p>
                    <p class="text-base sm:text-lg font-bold" id="session-timer">30:00</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6" role="alert">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-yellow-900">🗳️ Voting Instructions:</h3>
                <ul class="text-sm text-yellow-800 mt-2 space-y-1 list-disc list-inside ml-2">
                    <li>Click on candidate cards to select your preferred candidates</li>
                    <li>Click <strong>View Full Profile</strong> to read a candidate's background</li>
                    <li>Pay attention to the number of positions available for each role</li>
                    <li>You can change your selections before submitting</li>
                    <li><strong>Once submitted, votes cannot be changed</strong></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Voting Form --}}
    <form method="POST" action="{{ route('voting.submit-votes') }}" id="voting-form" novalidate>
        @csrf

        @forelse($positions as $position)
            {{-- Position Card --}}
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6" id="position-{{ $position->id }}">

                {{-- Position Header --}}
                <div class="bg-gray-50 px-4 sm:px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex-1">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ $position->title }}</h3>
                            <p class="text-xs sm:text-sm text-gray-600 mt-1">
                                Vote for up to <strong>{{ $position->vacant_count }}</strong> candidate(s)
                            </p>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-gray-100 text-gray-800 position-status"
                                data-position="{{ $position->id }}"
                                data-max="{{ $position->vacant_count }}"
                                aria-live="polite">
                                <span class="selected-count">0</span> / {{ $position->vacant_count }} selected
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Candidates Grid --}}
                <div class="p-4 sm:p-6">
                    @if($position->candidates->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" role="group" aria-label="Candidates for {{ $position->title }}">
                            @foreach($position->candidates as $candidate)
                                <div class="candidate-card relative cursor-pointer rounded-lg border-2 border-gray-200 hover:border-blue-300 hover:shadow-md"
                                    data-position-id="{{ $position->id }}"
                                    data-candidate-id="{{ $candidate->id }}"
                                    data-max-votes="{{ $position->vacant_count }}"
                                    tabindex="0"
                                    role="checkbox"
                                    aria-checked="false"
                                    aria-label="Select {{ $candidate->full_name }} for {{ $position->title }}">

                                    {{-- Hidden Checkbox --}}
                                    <input type="checkbox"
                                        name="votes[{{ $position->id }}][]"
                                        value="{{ $candidate->id }}"
                                        class="candidate-checkbox sr-only"
                                        data-position="{{ $position->id }}"
                                        aria-hidden="true">

                                    {{-- Selected Indicator --}}
                                    <div class="selected-indicator absolute top-3 right-3 z-10 hidden" aria-hidden="true">
                                        <div class="bg-blue-600 rounded-full p-1 shadow-lg">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="p-4 flex items-start gap-3 sm:gap-4">
                                        {{-- Candidate Image --}}
                                        <div class="flex-shrink-0">
                                            @if($candidate->image)
                                                <img src="{{ $candidate->profile_image_url }}"
                                                    alt="{{ $candidate->full_name }}"
                                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border-2 border-gray-200"
                                                    loading="lazy">
                                            @else
                                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center border-2 border-gray-200">
                                                    <span class="text-xl sm:text-2xl font-bold text-white">
                                                        {{ strtoupper(substr($candidate->first_name, 0, 1)) }}{{ strtoupper(substr($candidate->last_name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Candidate Info --}}
                                        <div class="flex-1 min-w-0"
                                            data-candidate-name="{{ $candidate->full_name }}"
                                            data-candidate-profile="{{ $candidate->background_profile !== 'No background profile provided' ? $candidate->background_profile : '' }}"
                                            data-candidate-image="{{ $candidate->image ? $candidate->profile_image_url : '' }}">

                                            <h4 class="text-sm sm:text-base font-semibold text-gray-900 break-words">
                                                {{ $candidate->full_name }}
                                            </h4>

                                            @if($candidate->background_profile && $candidate->background_profile !== 'No background profile provided')
                                                <p class="text-xs text-gray-500 mt-1 line-clamp-1 italic">
                                                    {{ Str::limit($candidate->background_profile, 60) }}
                                                </p>
                                                <button type="button"
                                                    class="view-profile-btn mt-2 inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-semibold focus:outline-none focus:underline transition-colors"
                                                    aria-label="View full profile of {{ $candidate->full_name }}">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    View Full Profile
                                                </button>
                                            @else
                                                <p class="text-xs text-gray-400 mt-1 italic">No profile available</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8" role="alert">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No candidates available for this position</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-lg overflow-hidden" role="alert">
                <div class="p-8 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No Active Positions</h3>
                    <p class="mt-2 text-sm text-gray-500">There are currently no positions available for voting.</p>
                </div>
            </div>
        @endforelse

        {{-- Submit Section --}}
        @if($positions->count() > 0)
            <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6 sticky bottom-4 z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Ready to submit your votes?</p>
                        <p class="text-xs text-gray-600 mt-1" id="vote-summary" aria-live="polite">
                            You must vote for at least one candidate before submitting.
                        </p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <button type="button"
                            id="review-btn"
                            disabled
                            aria-label="Submit your votes"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 sm:px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Submit My Vote
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>

{{-- Review Modal --}}
<div id="review-modal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-blue-600 text-white px-4 sm:px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h3 id="modal-title" class="text-lg sm:text-xl font-bold">Review Your Selections</h3>
            <button type="button"
                id="close-modal"
                class="text-white hover:text-gray-200 transition-colors"
                aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-4 sm:p-6 overflow-y-auto flex-1" id="review-content">
            {{-- Content populated by JavaScript --}}
        </div>

        <div class="bg-gray-50 px-4 sm:px-6 py-4 flex flex-col-reverse sm:flex-row justify-end gap-3 flex-shrink-0">
            <button type="button"
                id="close-modal-btn"
                class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Go Back
            </button>
            <button type="button"
                id="confirm-submit"
                class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Confirm & Submit
            </button>
        </div>
    </div>
</div>

{{-- Candidate Profile Modal --}}
<div id="profile-modal"
    class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[60] p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="profile-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">

        {{-- Modal Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-5 flex items-center gap-4">
            <div id="profile-modal-avatar" class="flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-blue-200 uppercase tracking-widest font-semibold mb-1">Candidate Profile</p>
                <h3 id="profile-modal-title" class="text-xl font-bold text-white truncate"></h3>
            </div>
            <button type="button"
                id="close-profile-modal"
                class="text-white/70 hover:text-white transition-colors flex-shrink-0 ml-2"
                aria-label="Close profile modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-1 h-5 bg-blue-600 rounded-full"></div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Background Profile</p>
            </div>
            <p id="profile-modal-body"
                class="text-gray-800 text-base font-medium leading-relaxed whitespace-pre-line"></p>
        </div>

        {{-- Modal Footer --}}
        <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-100">
            <button type="button"
                id="close-profile-modal-btn"
                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/voting/ballot.js') }}"></script>
@endpush
