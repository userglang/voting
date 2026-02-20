@extends('layouts.app')

@section('title', 'Select Branch - Voting System')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                <p class="text-xs mt-2 font-medium text-blue-600">Select Branch</p>
            </div>
            <div class="flex-1 border-t-2 border-gray-300 mx-2"></div>
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">2</div>
                <p class="text-xs mt-2 text-gray-500">Verify Identity</p>
            </div>
            <div class="flex-1 border-t-2 border-gray-300 mx-2"></div>
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">3</div>
                <p class="text-xs mt-2 text-gray-500">Update Info</p>
            </div>
            <div class="flex-1 border-t-2 border-gray-300 mx-2"></div>
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">4</div>
                <p class="text-xs mt-2 text-gray-500">Vote</p>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-8 text-white">
            <h2 class="text-3xl font-bold mb-2">Welcome to Online Voting</h2>
            <p class="text-blue-100">Please select your branch and search for your name to begin</p>
        </div>

        <!-- Form -->
        <div class="p-6">

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">📋 Instructions:</h3>
                <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Select your branch from the dropdown below</li>
                    <li>Type your name in the search box</li>
                    <li>Click on your name when it appears in the results</li>
                    <li>Follow the verification steps</li>
                </ol>
            </div>

            <!-- Branch Selection -->
            <div class="mb-6">
                <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Your Branch *
                </label>
                <select id="branch_id" name="branch_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" data-branch-number="{{ $branch->branch_number }}">
                            {{ $branch->branch_name }} ({{ $branch->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Box -->
            <div class="mb-6">
                <label for="search_term" class="block text-sm font-medium text-gray-700 mb-2">
                    Search Your Name *
                </label>
                <input type="text" id="search_term" name="search_term"
                    placeholder="Type your first name, last name, or member code..."
                    disabled
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Please select a branch first</p>
            </div>

            <!-- Loading Indicator -->
            <div id="loading" class="hidden mb-4">
                <div class="flex items-center justify-center py-4">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-600">Searching...</span>
                </div>
            </div>

            <!-- Search Results -->
            <div id="search-results" class="hidden mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Search Results:</h3>
                <div id="results-list" class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                    <!-- Results will be populated here -->
                </div>
            </div>

            <!-- No Results Message -->
            <div id="no-results" class="hidden mb-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        ⚠️ No members found. Please check your spelling or try a different name.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-6 bg-gray-100 border border-gray-300 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-gray-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-gray-900">Security & Privacy</h4>
                <p class="text-xs text-gray-600 mt-1">
                    Your voting information is encrypted and secure. All votes are anonymous and cannot be traced back to individual members.
                </p>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Submission -->
    <form id="verification-form" method="POST" action="{{ route('voting.show-verification') }}" class="hidden">
        @csrf
        <input type="hidden" name="branch_id" id="selected_branch_id">
        <input type="hidden" name="member_id" id="selected_member_id">
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const branchSelect = document.getElementById('branch_id');
    const searchInput = document.getElementById('search_term');
    const loading = document.getElementById('loading');
    const searchResults = document.getElementById('search-results');
    const resultsList = document.getElementById('results-list');
    const noResults = document.getElementById('no-results');
    const verificationForm = document.getElementById('verification-form');

    let searchTimeout;

    // Enable search when branch is selected
    branchSelect.addEventListener('change', function() {
        if (this.value) {
            searchInput.disabled = false;
            searchInput.placeholder = 'Type your first name, last name, or member code...';
            searchInput.focus();
        } else {
            searchInput.disabled = true;
            searchInput.placeholder = 'Type your first name, last name, or member code...';
            searchInput.value = '';
            searchResults.classList.add('hidden');
            noResults.classList.add('hidden');
        }
    });

    // Search on input
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);

        const searchTerm = this.value.trim();
        const branchId = branchSelect.value;

        if (searchTerm.length < 3) {
            searchResults.classList.add('hidden');
            noResults.classList.add('hidden');
            return;
        }

        if (!branchId) {
            alert('Please select a branch first');
            return;
        }

        // Debounce search
        searchTimeout = setTimeout(() => {
            searchMembers(branchId, searchTerm);
        }, 300);
    });

    function searchMembers(branchId, searchTerm) {
        loading.classList.remove('hidden');
        searchResults.classList.add('hidden');
        noResults.classList.add('hidden');

        fetch('{{ route("voting.search-member") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                branch_id: branchId,
                search_term: searchTerm
            })
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');

            if (data.success && data.members.length > 0) {
                displayResults(data.members);
            } else {
                noResults.classList.remove('hidden');
            }
        })
        .catch(error => {
            loading.classList.add('hidden');
            console.error('Search error:', error);
            alert('An error occurred while searching. Please try again.');
        });
    }

    function displayResults(members) {
        resultsList.innerHTML = '';

        members.forEach(member => {
            const resultItem = document.createElement('div');
            resultItem.className = 'p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition-colors';
            resultItem.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">${member.last_name},  ${member.first_name}</p>
                    </div>
                    <svg class="h-5 w-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            `;

            resultItem.addEventListener('click', () => {
                selectMember(member);
            });

            resultsList.appendChild(resultItem);
        });

        searchResults.classList.remove('hidden');
    }

    function selectMember(member) {
        document.getElementById('selected_branch_id').value = branchSelect.value;
        document.getElementById('selected_member_id').value = member.id;
        verificationForm.submit();
    }
});
</script>
@endpush
@endsection
