@extends('layouts.app')

@section('title', 'Vote Confirmed - Voting System')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Success Banner -->
    <div class="bg-green-50 border-2 border-green-500 rounded-lg p-8 mb-6 text-center">
        <div class="flex justify-center mb-4">
            <div class="bg-green-500 text-white rounded-full w-20 h-20 flex items-center justify-center">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <h2 class="text-3xl font-bold text-green-900 mb-2">Vote Successfully Submitted!</h2>
        <p class="text-green-800">Thank you for participating in the cooperative election</p>
    </div>

    <!-- Receipt Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <h3 class="text-2xl font-bold mb-1">Official Voting Receipt</h3>
            <p class="text-blue-100 text-sm">Please save this receipt for your records</p>
        </div>

        <!-- Control Number - Prominent Display -->
        <div class="bg-yellow-50 border-b-2 border-yellow-300 px-6 py-6">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Your Control Number</p>
                <div class="bg-white border-2 border-yellow-400 rounded-lg py-4 px-6 inline-block">
                    <p class="text-4xl font-bold text-red-600 tracking-wider font-mono">{{ $controlNumber }}</p>
                </div>
                <p class="text-xs text-gray-600 mt-3">
                    ⚠️ Keep this number for any inquiries about your vote
                </p>
            </div>
        </div>

        <!-- Voter Information -->
        <div class="px-6 py-6 border-b border-gray-200">
            <h4 class="font-bold text-gray-900 mb-4">Voter Information</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Name:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $member->full_name }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Member Code:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $member->code }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Branch:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ $branch->branch_name }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Date & Time:</span>
                    <span class="font-medium text-gray-900 ml-2">{{ now()->format('M d, Y h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Votes Cast -->
        <div class="px-6 py-6">
            <h4 class="font-bold text-gray-900 mb-4">Votes Cast</h4>

            @foreach($votes as $positionTitle => $positionVotes)
                <div class="mb-4 last:mb-0">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h5 class="font-semibold text-gray-900 mb-2">{{ $positionTitle }}</h5>
                        <ul class="space-y-1">
                            @foreach($positionVotes as $index => $vote)
                                <li class="flex items-center text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $vote->candidate->full_name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 text-center border-t border-gray-200">
            <p class="text-xs text-gray-600">
                This is an official voting receipt from the Cooperative Voting System<br>
                Generated on {{ now()->format('F d, Y') }} at {{ now()->format('h:i A') }}
            </p>
        </div>
    </div>

    <!-- Download Button -->
    <div class="flex justify-center mb-6">
        <a href="{{ route('voting.download-receipt') }}"
            class="inline-flex items-center px-8 py-4 bg-red-600 text-white font-bold text-lg rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors shadow-lg">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download Receipt (PDF)
        </a>
    </div>

    <!-- Important Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        <!-- What Happens Next -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center flex-shrink-0 mt-1">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h4 class="font-bold text-blue-900 mb-2">What Happens Next?</h4>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Your vote has been securely recorded</li>
                        <li>• Results will be announced after voting closes</li>
                        <li>• Check the cooperative notice board for updates</li>
                        <li>• Winners will be contacted directly</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Important Reminders -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="bg-yellow-600 text-white rounded-full w-10 h-10 flex items-center justify-center flex-shrink-0 mt-1">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h4 class="font-bold text-yellow-900 mb-2">Important Reminders</h4>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        <li>• Save your control number ({{ $controlNumber }})</li>
                        <li>• Download and keep your PDF receipt</li>
                        <li>• Votes cannot be changed after submission</li>
                        <li>• Contact support for any concerns</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Security & Privacy Notice -->
    <div class="bg-gray-100 border border-gray-300 rounded-lg p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-gray-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-4">
                <h4 class="font-bold text-gray-900 mb-2">🔒 Security & Privacy</h4>
                <p class="text-sm text-gray-700">
                    Your vote has been encrypted and stored securely. All votes are anonymous and cannot be traced back to individual members.
                    The control number is for verification purposes only and does not reveal your voting choices.
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    Thank you for participating in our democratic process. Your voice matters!
                </p>
            </div>
        </div>
    </div>

    <!-- Return to Home -->
    <div class="mt-8 text-center">
        <a href="{{ route('voting.select-branch') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Return to Home
        </a>
    </div>
</div>

@push('scripts')
<script>
// Prevent back button after voting
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

// Auto-print option
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === '1') {
        window.print();
    }
});
</script>
@endpush
@endsection
