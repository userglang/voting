@extends('layouts.app')

@section('title', 'Not Qualified to Vote - Voting System')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Main Card --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-red-500 to-red-700 px-6 py-8 text-white text-center">
            <div class="mb-4">
                <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2">Not Qualified to Vote</h1>
            <p class="text-red-100">You are not eligible to participate in this election</p>
        </div>

        {{-- Member Information (if available) --}}
        @if(isset($member) && isset($branch))
        <div class="bg-red-50 border-b border-red-200 px-6 py-4">
            <div class="flex items-center">
                <div class="bg-red-500 text-white rounded-full w-12 h-12 flex items-center justify-center font-bold text-lg flex-shrink-0" aria-hidden="true">
                    {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                </div>
                <div class="ml-4 min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 truncate">
                        {{ strtoupper($member->full_name) }}
                    </p>
                    <p class="text-sm text-gray-600 truncate">
                        Member Code: {{ $member->code }}
                    </p>
                    <p class="text-sm text-gray-600 truncate">
                        Branch: {{ $branch->branch_name }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Content --}}
        <div class="p-6 sm:p-8">

            {{-- Main Message --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-yellow-900 mb-2">
                            Voting Access Restricted
                        </h3>

                        @if(session('reason'))
                            {{-- Show specific reason if provided --}}
                            <div class="bg-white border border-yellow-300 rounded p-3 mb-3">
                                <p class="text-sm font-semibold text-red-700">
                                    {{ session('reason') }}
                                </p>
                            </div>
                        @endif

                        <p class="text-sm text-yellow-800">
                            Based on our records, you are currently not eligible to participate in this election.
                            @if(!session('reason'))
                                This may be due to one or more of the following reasons:
                            @else
                                Please review the requirements below:
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Voting Requirements --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    Voting Requirements
                </h3>
                <p class="text-sm text-blue-800 mb-3">
                    To be eligible to vote in this election, you must meet <strong>ALL</strong> of the following requirements:
                </p>
                <ul class="space-y-3 text-sm text-blue-800">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <strong>MIGS Status:</strong> You must be a Member in Good Standing (MIGS)
                            <p class="text-xs text-blue-700 mt-1">This means your membership is active and compliant with all cooperative policies</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <strong>Share Capital:</strong> Your share amount must be greater than or equal to ₱3,000.00
                            <p class="text-xs text-blue-700 mt-1">Minimum share capital investment is required for voting rights</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- Next Steps --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    What Should You Do?
                </h3>
                <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside ml-2">
                    <li><strong>Contact Branch:</strong> Reach out to verify your eligibility status</li>
                    <li><strong>Check Your Records:</strong> Ensure your membership information is up to date</li>
                    <li><strong>Update Information:</strong> Complete any pending registration or verification steps</li>
                    <li><strong>Follow Up:</strong> Ask about the requirements and timeline for voting eligibility</li>
                </ol>
            </div>

            {{-- Contact Information --}}
            <div class="bg-white border border-gray-300 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contact Information
                </h3>
                <div class="space-y-3 text-sm text-gray-700">
                    <p>
                        For questions about your voting eligibility or to resolve this issue,
                        please contact the cooperative personnel:
                    </p>

                    @if(config('app.support_email'))
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <strong>Email:</strong>
                            <a href="mailto:{{ config('app.support_email') }}" class="text-blue-600 hover:text-blue-700 ml-1">
                                {{ config('app.support_email') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(config('app.support_phone'))
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <div>
                            <strong>Phone:</strong>
                            <a href="tel:{{ config('app.support_phone') }}" class="text-blue-600 hover:text-blue-700 ml-1">
                                {{ config('app.support_phone') }}
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(config('app.office_hours'))
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <strong>Office Hours:</strong>
                            <span class="ml-1">{{ config('app.office_hours') }}</span>
                        </div>
                    </div>
                    @endif

                    @if(config('app.office_address'))
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <strong>Office Address:</strong><br>
                            <span class="ml-1 text-gray-600">{{ config('app.office_address') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center pt-6 border-t border-gray-200">
                <a href="{{ route('voting.select-branch') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Return to Home
                </a>

                @if(config('app.support_email'))
                <a href="mailto:{{ config('app.support_email') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contact Administrator
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Additional Help --}}
    <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-gray-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-gray-900">Important Note</h4>
                <p class="text-xs text-gray-600 mt-1">
                    This restriction is in place to ensure fair and secure elections.
                    All eligibility requirements are determined by the cooperative's bylaws and election policies.
                    Please contact the administration if you believe this is an error or if you need clarification
                    about your membership status.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
