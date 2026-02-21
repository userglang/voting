@extends('layouts.app')

@section('title', 'Already Voted - Voting System')

@section('content')
<div class="max-w-3xl mx-auto">

    <!-- Warning Banner -->
    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-8 mb-6 text-center">
        <div class="flex justify-center mb-4">
            <div class="bg-yellow-400 text-white rounded-full w-20 h-20 flex items-center justify-center">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
        <h2 class="text-3xl font-bold text-yellow-900 mb-2">You Have Already Voted</h2>
        <p class="text-yellow-800">Your vote has been recorded in our system</p>
    </div>

    <!-- Information Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-600 to-gray-800 px-6 py-6 text-white">
            <h3 class="text-2xl font-bold mb-1">Voting Status</h3>
            <p class="text-gray-200 text-sm">You have already cast your vote in this election</p>
        </div>

        <!-- Content -->
        <div class="p-6">

            <!-- Message -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-4">
                        <h4 class="font-bold text-blue-900 mb-2">Thank You for Participating!</h4>
                        <p class="text-sm text-blue-800">
                            Our records show that you have already submitted your vote for this election.
                            To ensure fairness and prevent duplicate voting, each member is allowed to vote only once.
                        </p>
                    </div>
                </div>
            </div>

            <!-- What This Means -->
            <div class="mb-6">
                <h4 class="font-bold text-gray-900 mb-3">What This Means:</h4>
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700">You are already registered for the upcoming General Assembly</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700">Your vote has been successfully recorded</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700">You cannot vote again in this election</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700">Your vote is secure and anonymous</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-700">Results will be announced after voting closes</span>
                    </li>
                </ul>
            </div>


            <!-- Important Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h5 class="text-sm font-medium text-yellow-900 mb-1">Lost Your Receipt?</h5>
                        <p class="text-xs text-yellow-800">
                            If you need a copy of your voting receipt or have questions about your vote,
                            please contact the cooperative office with your member code and control number (if available).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <div class="bg-blue-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Need Assistance?</h3>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-700 mb-4">
                If you believe this is an error or have questions about your voting status, please contact us:
            </p>
            <div class="space-y-2 text-sm">
                <!-- Facebook -->
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-gray-500 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22 12a10 10 0 10-11.63 9.87v-6.99h-2.34v-2.88h2.34V9.84c0-2.31 1.37-3.59 3.48-3.59.99 0 2.03.18 2.03.18v2.23h-1.14c-1.13 0-1.48.7-1.48 1.42v1.7h2.52l-.4 2.88h-2.12v6.99A10 10 0 0022 12z"/>
                    </svg>
                    <span class="text-gray-700">Facebook:
                        <a href="https://www.facebook.com/taranasaOIC/" target="_blank" class="text-blue-600 hover:text-blue-800">Visit our page</a>
                    </span>
                </div>

                <!-- Instagram -->
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-gray-500 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm0 2h10c1.654 0 3 1.346 3 3v10c0 1.654-1.346 3-3 3H7c-1.654 0-3-1.346-3-3V7c0-1.654 1.346-3 3-3zm5 2a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6zm4.5-.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                    </svg>
                    <span class="text-gray-700">Instagram:
                        <a href="https://www.instagram.com/orointegratedcoop/" target="_blank" class="text-blue-600 hover:text-blue-800">Follow us</a>
                    </span>
                </div>

                <!-- Website -->
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 0v6l4 2"/>
                    </svg>
                    <span class="text-gray-700">Website:
                        <a href="https://www.orointegrated.coop/" target="_blank" class="text-blue-600 hover:text-blue-800">www.orointegrated.coop</a>
                    </span>
                </div>

                <!-- Office Hours -->
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-700">Office Hours: Mon-Fri 8:00 AM - 3:30 PM, Sat 8:00 AM - 10:30 AM</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Home Button -->
    <div class="text-center">
        <a href="{{ route('voting.select-branch') }}"
            class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Return to Home
        </a>
    </div>
</div>
@endsection
