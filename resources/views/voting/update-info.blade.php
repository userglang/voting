@extends('layouts.app')

@section('title', 'Update Information - Voting System')

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
                <div class="w-10 h-10 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-xs mt-2 text-green-600 font-medium">Verify Identity</p>
            </div>
            <div class="flex-1 border-t-2 border-green-500 mx-2"></div>
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                <p class="text-xs mt-2 font-medium text-blue-600">Update Info</p>
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
            <h2 class="text-2xl font-bold mb-1">Update Your Information</h2>
            <p class="text-blue-100 text-sm">Please review and update your contact information</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('voting.update-info.submit') }}" class="p-6">
            @csrf

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">📝 Keep Your Information Current</h3>
                <p class="text-sm text-blue-800">
                    Please review and update your information below. This helps us keep accurate records and communicate important updates to you.
                </p>
            </div>

            <!-- Current Member Info Display -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Current Information on File:</h3>
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
                        <span class="text-gray-600">Gender:</span>
                        <span class="font-medium text-gray-900 ml-2">{{ ucfirst($member->gender ?? 'Not specified') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Birth Date:</span>
                        <span class="font-medium text-gray-900 ml-2">{{ $member->formatted_birth_date ?? 'Not specified' }}</span>
                    </div>
                </div>
            </div>

            <!-- Marital Status -->
            <div class="mb-6">
                <label for="marital_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Marital Status
                </label>
                <select id="marital_status" name="marital_status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('marital_status') border-red-300 @enderror">
                    <option value="single" {{ old('marital_status', $member->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="married" {{ old('marital_status', $member->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                    <option value="divorced" {{ old('marital_status', $member->marital_status) == 'Separated' ? 'selected' : '' }}>Separated</option>
                    <option value="widowed" {{ old('marital_status', $member->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                </select>
                @error('marital_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Religion -->
            <div class="mb-6">
                <label for="religion" class="block text-sm font-medium text-gray-700 mb-2">
                    Religion
                </label>
                <input type="text"
                    id="religion"
                    name="religion"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('religion') border-red-300 @enderror"
                    placeholder="Your religion"
                    value="{{ old('religion', $member->religion) }}">
                @error('religion')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Address -->
            <div class="mb-6">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Complete Address *
                </label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-300 @enderror"
                    placeholder="Street, Barangay, City/Municipality, Province">{{ old('address', $member->address) }}</textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Number -->
            <div class="mb-6">
                <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Number *
                </label>
                <input type="tel"
                    id="contact_number"
                    name="contact_number"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-300 @enderror"
                    placeholder="09XX-XXX-XXXX"
                    value="{{ old('contact_number', $member->contact_number) }}">
                @error('contact_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address <span class="text-gray-500 font-normal">(Optional)</span>
                </label>
                <input type="email"
                    id="email"
                    name="email"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @enderror"
                    placeholder="your.email@example.com"
                    value="{{ old('email', $member->email) }}">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Occupation -->
            <div class="mb-6">
                <label for="occupation" class="block text-sm font-medium text-gray-700 mb-2">
                    Occupation <span class="text-gray-500 font-normal">(Optional)</span>
                </label>
                <input type="text"
                    id="occupation"
                    name="occupation"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('occupation') border-red-300 @enderror"
                    placeholder="Your current occupation"
                    value="{{ old('occupation', $member->occupation) }}">
                @error('occupation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Information Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    ℹ️ <strong>Note:</strong> Only the fields marked with (*) are required. However, providing complete information helps us serve you better.
                </p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-4 pt-4">
                <button type="submit"
                    class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Continue to Voting →</button>
            </div>
        </form>
    </div>

    <!-- Privacy Notice -->
    <div class="mt-6 bg-gray-100 border border-gray-300 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-gray-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-gray-900">Privacy & Data Protection</h4>
                <p class="text-xs text-gray-600 mt-1">
                    Your information is stored securely and will only be used for cooperative communications and administration. We will never share your personal information with third parties.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format contact number
    const contactInput = document.getElementById('contact_number');
    contactInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 11) {
            value = value.slice(0, 11);
        }
        this.value = value;
    });
});
</script>
@endpush
@endsection
