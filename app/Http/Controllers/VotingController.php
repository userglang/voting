<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Position;
use App\Models\Candidate;
use App\Models\Vote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class VotingController extends Controller
{
    // ─── Session Key Constants ────────────────────────────────────────────────
    private const SESSION_BRANCH_ID     = 'voting.branch_id';
    private const SESSION_MEMBER_ID     = 'voting.member_id';
    private const SESSION_MEMBER_CODE   = 'voting.member_code';
    private const SESSION_BRANCH_NUMBER = 'voting.branch_number';
    private const SESSION_VERIFIED      = 'voting.verified';
    private const SESSION_VERIFIED_AT   = 'voting.verified_at';
    private const SESSION_CONTROL_NUM   = 'voting.control_number';
    private const SESSION_VOTES_DONE    = 'voting.votes_submitted';
    private const SESSION_ALREADY_VOTED = 'voting.already_voted';

    private const SESSION_VERIFICATION_EXPIRY_MINUTES = 30;

    // ─── Step 1: Select Branch ────────────────────────────────────────────────

    public function selectBranch()
    {
        if ($this->isVerifiedSession()) {
            return redirect()->route('voting.update-info');
        }

        $branches = Branch::where('is_active', true)
            ->orderBy('branch_name')
            ->get(['id', 'branch_number', 'branch_name', 'code']);

        return view('voting.select-branch', compact('branches'));
    }

    // ─── Step 2: Search Member (AJAX) ─────────────────────────────────────────

    public function searchMember(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'search_term' => 'required|string|min:3|max:100',
        ]);

        try {
            $branch = Branch::findOrFail($request->branch_id);

            $search = trim($request->search_term);

            if (str_contains($search, ',')) {
                // Handle: "Last, First"
                [$last, $first] = array_map('trim', explode(',', $search, 2));
                $search = $first . ' ' . $last;
            }

            $terms = collect(explode(' ', strtolower($search)))->filter();

            $members = Member::where('branch_number', $branch->branch_number)
                ->where('is_active', true)
                ->when($terms->isNotEmpty(), function ($query) use ($terms) {
                    $query->where(function ($q) use ($terms) {
                        foreach ($terms as $term) {
                            $q->where(function ($sub) use ($term) {
                                $sub->where('first_name', 'LIKE', "%{$term}%")
                                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                                    ->orWhere('code', 'LIKE', "%{$term}%");
                            });
                        }
                    });
                })
                ->limit(15)
                ->get()
                ->map(fn($m) => [
                    'id'          => $m->id,
                    'code'        => $m->code,
                    'full_name'   => $m->full_name,
                    'first_name'  => $m->first_name,
                    'last_name'   => $m->last_name,
                    'middle_name' => $m->middle_name,
                    'address'     => $m->address,
                ]);

            return response()->json([
                'success' => true,
                'members' => $members
            ]);

        } catch (\Exception $e) {
            Log::error('Member search error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching. Please try again.',
            ], 500);
        }
    }

    // ─── Step 3: Show Verification Form ──────────────────────────────────────

    public function showVerification(Request $request)
    {
        if ($this->isVerifiedSession()) {
            return redirect()->route('voting.update-info');
        }

        $branchId = $request->input('branch_id', Session::get(self::SESSION_BRANCH_ID));
        $memberId = $request->input('member_id', Session::get(self::SESSION_MEMBER_ID));

        if (!$branchId || !$memberId) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Please select a branch and member first.']);
        }

        try {
            $branch = Branch::findOrFail($branchId);
            $member = Member::where('id', $memberId)
                ->where('branch_number', $branch->branch_number)
                ->where('is_active', true)
                ->firstOrFail();

            Session::put(self::SESSION_BRANCH_ID, $branch->id);
            Session::put(self::SESSION_MEMBER_ID, $member->id);

            return view('voting.verify', compact('branch', 'member'));

        } catch (\Exception $e) {
            Log::error('Show verification error', [
                'error'     => $e->getMessage(),
                'branch_id' => $branchId,
                'member_id' => $memberId,
                'ip'        => $request->ip(),
            ]);

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'Member not found or invalid selection.']);
        }
    }

    // ─── Step 4: Verify Identity ──────────────────────────────────────────────

    public function verifyIdentity(Request $request): RedirectResponse
    {
        $request->validate([
            'share_account_last4' => 'required|digits:4',
            'middle_name'         => 'nullable|string|max:100',
            'birth_date'          => 'nullable|date|before:today',
        ]);

        if (empty($request->middle_name) && empty($request->birth_date)) {
            throw ValidationException::withMessages([
                'verification' => ['Please answer at least one security question.'],
            ]);
        }

        [$branch, $member] = $this->resolveSessionBranchAndMember($request);

        if (!$branch || !$member) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        $preservedInput = $request->only(['share_account_last4', 'middle_name', 'birth_date']);

        // Verify share account last 4 digits
        $shareAccountLast4 = substr(str_replace('-', '', $member->share_account), -4);

        if ($shareAccountLast4 !== $request->share_account_last4) {
            return back()->withInput($preservedInput)
                ->withErrors(['share_account_last4' => 'The last 4 digits do not match our records. Please try again.']);
        }

        // Verify at least one security question passes
        if (!$this->verifySecurityQuestions($request, $member)) {
            return back()->withInput($preservedInput)
                ->withErrors(['verification' => 'The information provided does not match our records. Please check and try again.']);
        }

        // Check if already voted
        if ($this->memberHasVoted($member)) {
            Session::put(self::SESSION_MEMBER_CODE, $member->code);
            Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);
            $this->clearSessionForAlreadyVoted();

            return redirect()->route('voting.already-voted');
        }

        // Mark session as verified
        Session::put(self::SESSION_VERIFIED, true);
        Session::put(self::SESSION_VERIFIED_AT, now());

        return redirect()->route('voting.update-info');
    }

    // ─── Step 5: Show Update Info Form ───────────────────────────────────────

    public function showUpdateInfo()
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        $member = $this->getSessionMember();

        if ($member->is_registered) {
            return redirect()->route('voting.show-ballot');
        }

        return view('voting.update-info', compact('member'));
    }

    // ─── Step 6: Save Updated Information ────────────────────────────────────

    public function updateInfo(Request $request): RedirectResponse
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        $request->validate([
            'tin'            => 'nullable|string|max:20',
            'address'        => 'required|string|max:255',
            'email'          => 'nullable|email|max:100|unique:members,email',
            'contact_number' => 'required|string|max:20',
            'occupation'     => 'nullable|string|max:100',
            'marital_status' => 'required|string|in:single,married,separated,widowed',
            'religion'       => 'required|string|max:100',
        ]);

        try {
            $member = $this->getSessionMember();

            $member->update([
                'tin'               => $request->tin,
                'address'           => $request->address,
                'email'             => $request->email,
                'contact_number'    => $request->contact_number,
                'occupation'        => $request->occupation,
                'marital_status'    => $request->marital_status,
                'religion'          => $request->religion,
                'is_registered'     => true,
                'registration_type' => 'Online',
            ]);

            // Check eligibility (MIGS, share amount, already voted)
            if ($redirect = $this->checkVotingEligibility($member, $request->ip())) {
                return $redirect;
            }

            // Store codes for downstream validation
            Session::put(self::SESSION_MEMBER_CODE, $member->code);
            Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);

            return redirect()->route('voting.show-ballot')
                ->with('success', 'Information updated successfully. You may now cast your vote.');

        } catch (ModelNotFoundException $e) {
            Log::error('Update info: Member not found', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'Member not found. Please start again.']);

        } catch (\Exception $e) {
            Log::error('Update info error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'Failed to update information. Please try again.']);
        }
    }

    // ─── Not Qualified Page ───────────────────────────────────────────────────

    public function notQualified()
    {
        if (!session('reason')) {
            return redirect()->route('voting.select-branch');
        }

        return view('voting.not-qualified');
    }

    // ─── Step 7: Show Ballot ──────────────────────────────────────────────────

    public function showBallot()
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        try {
            $member = $this->getSessionMember();
            $branch = $this->getSessionBranch();

            if ($redirect = $this->checkVotingEligibility($member, request()->ip())) {
                return $redirect;
            }

            $positions = Position::where('is_active', true)
                ->orderBy('priority')
                ->with(['candidates' => fn($q) => $q->orderBy('last_name')->orderBy('first_name')])
                ->get();

            if ($positions->isEmpty()) {
                return redirect()->route('voting.select-branch')
                    ->with('warning', 'No active positions available for voting at this time. Please contact the administrator.');
            }

            Session::put(self::SESSION_MEMBER_CODE, $member->code);
            Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);

            return view('voting.ballot', compact('member', 'positions'));

        } catch (ModelNotFoundException $e) {
            Log::error('Ballot: Member or branch not found', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            Session::flush();

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'Member or branch not found. Please start again.']);

        } catch (\Exception $e) {
            Log::error('Ballot access error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'An error occurred. Please try again.']);
        }
    }

    // ─── Step 8: Submit Votes ─────────────────────────────────────────────────

    public function submitVotes(Request $request): RedirectResponse
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        $request->validate([
            'votes'     => 'required|array',
            'votes.*'   => 'required|array',
            'votes.*.*' => 'exists:candidates,id',
        ]);

        DB::beginTransaction();

        try {
            $member = $this->getSessionMember();

            if ($this->memberHasVoted($member)) {
                DB::rollBack();
                Session::put(self::SESSION_MEMBER_CODE, $member->code);
                Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);
                $this->clearSessionForAlreadyVoted();

                return redirect()->route('voting.already-voted');
            }

            $votesData     = $this->buildVotesData($request->votes);
            $controlNumber = null;

            foreach ($votesData as $voteData) {
                $vote = Vote::create([
                    'member_code'   => $member->code,
                    'branch_number' => $member->branch_number,
                    'candidate_id'  => $voteData['candidate_id'],
                    'online_vote'   => true,
                ]);

                $controlNumber ??= $vote->control_number;
            }

            DB::commit();

            Session::put(self::SESSION_CONTROL_NUM, $controlNumber);
            Session::put(self::SESSION_VOTES_DONE, true);

            return redirect()->route('voting.confirmation');

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Vote submission error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return back()->withErrors(['error' => 'Failed to submit votes. Please try again.']);
        }
    }

    // ─── Step 8b: Abstain ────────────────────────────────────────────────────

    public function abstain(Request $request): RedirectResponse
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        $this->clearFullSession();

        return redirect()->route('voting.select-branch')
            ->with('info', 'You have chosen not to vote at this time. Your session has been cleared.');
    }

    // ─── Step 9: Show Confirmation ────────────────────────────────────────────

    public function showConfirmation()
    {
        if (!$this->isVerifiedSession()) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Session expired. Please start again.']);
        }

        if (!Session::get(self::SESSION_VOTES_DONE)) {
            return redirect()->route('voting.show-ballot')
                ->with('error', 'Please cast your vote first.');
        }

        try {
            $member        = $this->getSessionMember();
            $branch        = $this->getSessionBranch();
            $controlNumber = Session::get(self::SESSION_CONTROL_NUM);

            $voteExists = Vote::where('member_code', $member->code)
                ->where('branch_number', $member->branch_number)
                ->where('control_number', $controlNumber)
                ->exists();

            if (!$voteExists) {
                return redirect()->route('voting.show-ballot')
                    ->with('error', 'Please cast your vote first.');
            }

            $votes = $this->getGroupedVotes($member, $controlNumber);

            return view('voting.confirmation', compact('member', 'branch', 'controlNumber', 'votes'));

        } catch (\Exception $e) {
            Log::error('Confirmation error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'An error occurred. Please try again.']);
        }
    }

    // ─── Step 10: Download PDF Receipt ───────────────────────────────────────

    public function downloadReceipt()
    {
        if (!Session::get(self::SESSION_VOTES_DONE)) {
            return redirect()->route('voting.select-branch');
        }

        try {
            $member        = $this->getSessionMember();
            $branch        = $this->getSessionBranch();
            $controlNumber = Session::get(self::SESSION_CONTROL_NUM);
            $votes         = $this->getGroupedVotes($member, $controlNumber);
            $pdfPath       = $this->generateReceiptPDF($member, $branch, $controlNumber, $votes);

            $this->clearFullSession();

            return response()->download($pdfPath, "voting_receipt_{$controlNumber}.pdf")
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Receipt download error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return back()->withErrors(['error' => 'Failed to generate receipt. Please contact support.']);
        }
    }

    // ─── Already Voted Page ───────────────────────────────────────────────────

    public function alreadyVoted()
    {
        $memberCode   = Session::get(self::SESSION_MEMBER_CODE);
        $branchNumber = Session::get(self::SESSION_BRANCH_NUMBER);

        if (!$memberCode || !$branchNumber) {
            return redirect()->route('voting.select-branch');
        }

        $hasVoted = Vote::where('member_code', $memberCode)
            ->where('branch_number', $branchNumber)
            ->exists();

        if (!$hasVoted) {
            return redirect()->route('voting.select-branch');
        }

        $this->clearFullSession();

        return view('voting.already-voted');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function resolveSessionBranchAndMember(Request $request): array
    {
        $branchId = Session::get(self::SESSION_BRANCH_ID);
        $memberId = Session::get(self::SESSION_MEMBER_ID);

        if (!$branchId || !$memberId) {
            return [null, null];
        }

        try {
            $branch = Branch::findOrFail($branchId);
            $member = Member::findOrFail($memberId);

            return [$branch, $member];
        } catch (\Exception $e) {
            Log::error('Failed to resolve session branch/member', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return [null, null];
        }
    }

    private function getSessionMember(): Member
    {
        return Member::findOrFail(Session::get(self::SESSION_MEMBER_ID));
    }

    private function getSessionBranch(): Branch
    {
        return Branch::findOrFail(Session::get(self::SESSION_BRANCH_ID));
    }

    private function memberHasVoted(Member $member): bool
    {
        return Vote::where('member_code', $member->code)
            ->where('branch_number', $member->branch_number)
            ->exists();
    }

    private function verifySecurityQuestions(Request $request, Member $member): bool
    {
        if ($request->filled('middle_name') &&
            strtolower(trim($request->middle_name)) === strtolower(trim($member->middle_name))) {
            return true;
        }

        if ($request->filled('birth_date') &&
            $member->birth_date !== null &&
            $request->birth_date === $member->birth_date->format('Y-m-d')) {
            return true;
        }

        return false;
    }

    private function checkVotingEligibility(Member $member, string $ip): ?RedirectResponse
    {
        if (!$member->is_migs) {
            $this->clearFullSession();

            return redirect()->route('voting.not-qualified')
                ->with('reason', 'Only MIGS (Member in Good Standing) can participate in voting.');
        }

        if (!$member->share_amount) {
            $this->clearFullSession();

            return redirect()->route('voting.not-qualified')
                ->with('reason', 'Your share amount must be greater than ₱3,000.00 to participate in voting.');
        }

        if ($this->memberHasVoted($member)) {
            Session::put(self::SESSION_MEMBER_CODE, $member->code);
            Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);
            $this->clearSessionForAlreadyVoted();

            return redirect()->route('voting.already-voted');
        }

        return null;
    }

    private function buildVotesData(array $votesInput): array
    {
        $votesData = [];

        foreach ($votesInput as $positionId => $candidateIds) {
            $position = Position::findOrFail($positionId);

            if (count($candidateIds) > $position->vacant_count) {
                throw ValidationException::withMessages([
                    'votes' => ["Too many votes for position: {$position->title}. Maximum: {$position->vacant_count}"],
                ]);
            }

            foreach ($candidateIds as $candidateId) {
                $candidate = Candidate::where('id', $candidateId)
                    ->where('position_id', $positionId)
                    ->firstOrFail();

                $votesData[] = [
                    'candidate_id' => $candidate->id,
                    'position_id'  => $position->id,
                ];
            }
        }

        return $votesData;
    }

    private function getGroupedVotes(Member $member, string $controlNumber)
    {
        return Vote::where('member_code', $member->code)
            ->where('branch_number', $member->branch_number)
            ->where('control_number', $controlNumber)
            ->with(['candidate.position'])
            ->get()
            ->groupBy('candidate.position.title');
    }

    private function generateReceiptPDF(Member $member, Branch $branch, string $controlNumber, $votes): string
    {
        $votesArray = [];

        foreach ($votes as $positionTitle => $positionVotes) {
            $votesArray[$positionTitle] = $positionVotes
                ->map(fn($vote) => $vote->candidate->full_name)
                ->values()
                ->toArray();
        }

        $data = [
            'control_number' => $controlNumber,
            'member'         => $member,
            'branch'         => $branch,
            'date'           => now()->format('F d, Y'),
            'time'           => now()->format('h:i A'),
            'votes'          => $votesArray,
            'total_votes'    => $votes->flatten()->count(),
        ];

        $pdf = Pdf::loadView('voting.receipt-pdf', $data)
            ->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $tempDir  = storage_path('app/temp');
        $filename = "receipt_{$controlNumber}_" . time() . '.pdf';
        $filepath = "{$tempDir}/{$filename}";

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdf->save($filepath);

        return $filepath;
    }

    private function isVerifiedSession(): bool
    {
        if (!Session::get(self::SESSION_VERIFIED)) {
            return false;
        }

        $verifiedAt = Session::get(self::SESSION_VERIFIED_AT);

        if (!$verifiedAt || now()->diffInMinutes($verifiedAt) > self::SESSION_VERIFICATION_EXPIRY_MINUTES) {
            $this->clearFullSession();

            return false;
        }

        return true;
    }

    private function clearSessionForAlreadyVoted(): void
    {
        Session::put(self::SESSION_ALREADY_VOTED, true);

        Session::forget([
            self::SESSION_BRANCH_ID,
            self::SESSION_MEMBER_ID,
            self::SESSION_VERIFIED,
            self::SESSION_VERIFIED_AT,
            self::SESSION_CONTROL_NUM,
            self::SESSION_VOTES_DONE,
        ]);
    }

    private function clearFullSession(): void
    {
        Session::forget([
            self::SESSION_BRANCH_ID,
            self::SESSION_MEMBER_ID,
            self::SESSION_MEMBER_CODE,
            self::SESSION_BRANCH_NUMBER,
            self::SESSION_VERIFIED,
            self::SESSION_VERIFIED_AT,
            self::SESSION_CONTROL_NUM,
            self::SESSION_VOTES_DONE,
            self::SESSION_ALREADY_VOTED,
        ]);
    }
}
