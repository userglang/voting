<?php

namespace App\Livewire\Voting;

use App\Models\Branch;
use App\Models\Member;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.voting')]
class BranchSelection extends Component
{
    public $selectedBranch = '';
    public $searchTerm = '';
    public $results;
    public $selectedMember = null;

    // Verification fields
    public $last4ShareAccount = '';
    public $middleName = '';
    public $birthDate = '';
    public $verificationError = null;

    protected function rules()
    {
        return [
            'selectedBranch' => 'required|exists:branches,branch_number',
            'searchTerm' => 'nullable|string|min:3',
            'last4ShareAccount' => 'required|digits:4',
            'middleName' => 'nullable|string',
            'birthDate' => 'nullable|date',
        ];
    }

    protected $messages = [
        'selectedBranch.required' => 'Please select a branch to continue.',
        'selectedBranch.exists' => 'Invalid branch selected.',
        'searchTerm.min' => 'Search term must be at least 3 characters.',
        'last4ShareAccount.required' => 'Last 4 digits is required.',
        'last4ShareAccount.digits' => 'Last 4 digits must be exactly 4 numbers.',
    ];

    public function mount()
    {
        $this->results = collect();
    }

    /**
     * Live search members
     */
    public function updatedSearchTerm()
    {
        $this->validateOnly('searchTerm');

        if (strlen($this->searchTerm) >= 3 && $this->selectedBranch) {
            $this->results = Member::where('branch_number', $this->selectedBranch)
                ->where(function ($query) {
                    $query->where('first_name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%');
                })
                ->limit(20)
                ->get();
        } else {
            $this->results = collect();
        }
    }

    /**
     * Reset when branch changes
     */
    public function updatedSelectedBranch()
    {
        $this->searchTerm = '';
        $this->results = collect();
        $this->selectedMember = null;
        $this->resetVerificationFields();

        $this->validateOnly('selectedBranch');
    }

    /**
     * Select member
     */
    public function selectResult($memberId)
    {
        $this->validate(['selectedBranch' => 'required|exists:branches,branch_number']);

        $member = Member::where('branch_number', $this->selectedBranch)
            ->where('id', $memberId)
            ->first();

        if ($member) {
            $this->selectedMember = $member;
            $this->verificationError = null;
            $this->dispatch('member-selected');
        }
    }

    /**
     * Verify identity
     */
    public function verifyIdentity()
    {
        $this->verificationError = null;

        $this->validate([
            'last4ShareAccount' => 'required|digits:4',
        ]);

        if (!$this->middleName && !$this->birthDate) {
            $this->verificationError = 'Please answer at least one security question (Middle Name or Birth Date).';
            return;
        }

        if (!$this->selectedMember) {
            $this->verificationError = 'No member selected.';
            return;
        }

        $member = $this->selectedMember;

        // Validate last 4 digits
        if (substr($member->share_account_number, -4) !== $this->last4ShareAccount) {
            $this->verificationError = 'Invalid share account details.';
            return;
        }

        $middleMatch = false;
        $birthMatch = false;

        if ($this->middleName) {
            $middleMatch = strtolower(trim($member->middle_name)) === strtolower(trim($this->middleName));
        }

        if ($this->birthDate) {
            $birthMatch = $member->birth_date === $this->birthDate;
        }

        if (!$middleMatch && !$birthMatch) {
            $this->verificationError = 'Security answer does not match our records.';
            return;
        }

        // SUCCESS: Store session and redirect
        session()->put('verified_member_id', $member->id);

        return redirect()->route('voting.ballot');
    }

    /**
     * Reset verification fields
     */
    private function resetVerificationFields()
    {
        $this->last4ShareAccount = '';
        $this->middleName = '';
        $this->birthDate = '';
        $this->verificationError = null;
    }

    public function render()
    {
        return view('livewire.voting.branch-selection', [
            'branches' => Branch::where('is_active', true)
                ->orderBy('branch_name')
                ->get(),
        ]);
    }
}
