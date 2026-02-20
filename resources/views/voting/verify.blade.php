@extends('layouts.app')

@section('title', 'Verify Identity - Voting System')

@section('content')
<div class="max-w-3xl mx-auto">

    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-xs mt-2 text-green-600 font-medium">Select Branch</p>
            </div>
            <div class="flex-1 border-t-2 border-green-500 mx-2"></div>
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                <p class="text-xs mt-2 font-medium text-blue-600">Verify Identity</p>
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
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <h2 class="text-2xl font-bold mb-1">Identity Verification</h2>
            <p class="text-blue-100 text-sm">Please verify your identity to continue</p>
        </div>

        <!-- Member Info -->
        <div class="bg-blue-50 border-b border-blue-200 px-6 py-4">
            <div class="flex items-center">
                <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center font-bold text-lg">
                    {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                </div>
                <div class="ml-4">
                    <p class="font-semibold text-gray-900">{{ strtoupper($member->first_name) }} {{ strtoupper($member->last_name) }}</p>
                    <p class="text-sm text-gray-600">Branch: {{ $branch->branch_name }}</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('voting.verify-identity') }}" class="p-6">
            @csrf

            <!-- Instructions -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-yellow-900 mb-2">🔐 Security Verification Required</h3>
                <p class="text-sm text-yellow-800 mb-2">
                    To protect your account, please provide the following information:
                </p>
                <ol class="text-sm text-yellow-800 space-y-1 list-decimal list-inside ml-2">
                    <li>Last 4 digits of your share account number</li>
                    <li>At least ONE security question (middle name OR birth date)</li>
                </ol>
            </div>

            <!-- Share Account Verification -->
            <div class="mb-6">
                <label for="share_account_last4" class="block text-sm font-medium text-gray-700 mb-2">
                    Last 4 Digits of Share Account Number *
                </label>
                <input type="text"
                    id="share_account_last4"
                    name="share_account_last4"
                    maxlength="4"
                    pattern="[0-9]{4}"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg tracking-widest font-mono @error('share_account_last4') border-red-300 @enderror"
                    placeholder="####">
                @error('share_account_last4')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Enter only the last 4 digits</p>
            </div>

            <div class="border-t border-gray-200 my-6"></div>

            <!-- Security Questions -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">
                    Security Questions <span class="text-gray-500 font-normal">(Answer at least ONE)</span>
                </h3>

                <!-- Middle Name -->
                <div class="mb-4">
                    <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-2">
                        What is your middle name?
                    </label>
                    <input type="text"
                        id="middle_name"
                        name="middle_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('middle_name') border-red-300 @enderror"
                        placeholder="Enter your middle name">
                    @error('middle_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Birth Date -->
                <div class="mb-4">
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">
                        What is your date of birth?
                    </label>
                    <input type="date"
                        id="birth_date"
                        name="birth_date"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('birth_date') border-red-300 @enderror">
                    @error('birth_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @error('verification')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    </div>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('voting.select-branch') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Back to search
                </a>
                <button type="submit"
                    class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Verify & Continue
                </button>
            </div>
        </form>
    </div>

    <!-- Security Notice -->
    <div class="mt-6 bg-gray-100 border border-gray-300 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-gray-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-gray-900">Important Security Notice</h4>
                <ul class="text-xs text-gray-600 mt-1 space-y-1">
                    <li>• Your information is never shared with anyone</li>
                    <li>• We will never ask for your full share account number</li>
                    <li>• You have limited verification attempts for security</li>
                    <li>• Contact support if you're having trouble verifying</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const shareAccountInput = document.getElementById('share_account_last4');

    // Only allow numbers
    shareAccountInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const shareAccount = document.getElementById('share_account_last4').value;
        const middleName = document.getElementById('middle_name').value.trim();
        const birthDate = document.getElementById('birth_date').value;

        if (shareAccount.length !== 4) {
            e.preventDefault();
            alert('Please enter exactly 4 digits for the share account.');
            return;
        }

        if (!middleName && !birthDate) {
            e.preventDefault();
            alert('Please answer at least one security question (middle name OR birth date).');
            return;
        }
    });
});
</script>
@endpush
@endsection
