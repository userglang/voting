@php use Illuminate\Support\Str; @endphp

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">
                Oro Integrated Cooperative
            </h2>
            <p class="text-gray-600 mt-2">
                Please select your branch to begin the voting process
            </p>
        </div>

        {{-- ========================= --}}
        {{-- SUCCESS MESSAGE --}}
        {{-- ========================= --}}
        @if(session()->has('message'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl">
                {{ session('message') }}
            </div>
        @endif

        {{-- ========================= --}}
        {{-- BRANCH SELECTION --}}
        {{-- ========================= --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Select Your Branch <span class="text-red-500">*</span>
            </label>

            <select wire:model.live="selectedBranch"
                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500">

                <option value="">-- Select Branch --</option>

                @foreach($branches as $branch)
                    <option value="{{ $branch->branch_number }}">
                        {{ $branch->branch_name }} ({{ $branch->code }})
                    </option>
                @endforeach
            </select>

            @error('selectedBranch')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- ========================= --}}
        {{-- SEARCH MEMBER --}}
        {{-- ========================= --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Search Your Name
            </label>

            <input type="text"
                wire:model.live="searchTerm"
                placeholder="Type your first name or last name..."
                @if(!$selectedBranch) disabled @endif
                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500
                       @if(!$selectedBranch) bg-gray-100 cursor-not-allowed @endif">

            @error('searchTerm')
                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror

            @if(!$selectedBranch)
                <p class="text-xs text-gray-500 mt-2">
                    Please select a branch first.
                </p>
            @endif
        </div>

        {{-- ========================= --}}
        {{-- SEARCH RESULTS --}}
        {{-- ========================= --}}
        @if($selectedBranch && strlen($searchTerm) >= 3)
            <div class="mb-6">

                @if($results->isEmpty())
                    <div class="p-6 text-center bg-gray-50 border rounded-xl text-gray-500">
                        No results found.
                    </div>
                @else
                    <div class="border rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700">
                            Found {{ $results->count() }} {{ Str::plural('member', $results->count()) }}
                        </div>

                        <ul class="divide-y max-h-64 overflow-y-auto">
                            @foreach($results as $result)
                                <li>
                                    <button
                                        wire:click="selectResult({{ $result->id }})"
                                        class="w-full text-left px-4 py-3 hover:bg-blue-50 transition">

                                        <div class="flex items-center justify-between">
                                            <span class="font-medium">
                                                {{ $result->first_name }} {{ $result->last_name }}
                                            </span>

                                            @if($selectedMember && $selectedMember->id === $result->id)
                                                <span class="text-blue-600 text-sm font-semibold">
                                                    Selected
                                                </span>
                                            @endif
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- ========================= --}}
        {{-- VERIFY IDENTITY SECTION --}}
        {{-- ========================= --}}
        @if($selectedMember)
            <div class="mt-8 p-6 bg-blue-50 border border-blue-100 rounded-xl">

                <h3 class="text-lg font-semibold mb-4 text-blue-900">
                    Verify Identity
                </h3>

                {{-- Verification Error --}}
                @if($verificationError)
                    <div class="mb-4 text-red-600 text-sm">
                        {{ $verificationError }}
                    </div>
                @endif

                {{-- Last 4 Digits --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        Enter Last 4 Digits of Share Account <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                        maxlength="4"
                        wire:model.defer="last4ShareAccount"
                        class="w-full px-4 py-2 border rounded-lg">

                    @error('last4ShareAccount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Middle Name --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        Middle Name (optional)
                    </label>

                    <input type="text"
                        wire:model.defer="middleName"
                        class="w-full px-4 py-2 border rounded-lg">
                </div>

                {{-- Birth Date --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        Birth Date (optional)
                    </label>

                    <input type="date"
                        wire:model.defer="birthDate"
                        class="w-full px-4 py-2 border rounded-lg">
                </div>

                <button
                    wire:click="verifyIdentity"
                    class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    Verify & Continue
                </button>
            </div>
        @endif

        {{-- ========================= --}}
        {{-- IMPORTANT NOTICE --}}
        {{-- ========================= --}}
        <div class="mt-8 p-5 bg-gray-50 border rounded-xl">
            <h3 class="font-semibold text-gray-800 mb-2">Important Notice:</h3>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>• Select the branch where you are registered</li>
                <li>• Enter correct share account information</li>
                <li>• Answer at least ONE security question</li>
            </ul>
        </div>

    </div>
</div>
