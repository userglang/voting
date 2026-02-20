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
            $branch  = Branch::findOrFail($request->branch_id);
            $search  = strtolower(trim($request->search_term));

            $members = Member::where('branch_number', $branch->branch_number)
                ->where('is_active', true)
                ->where(function ($query) use ($search, $request) {
                    $query->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ["%{$search}%"])
                        ->orWhere('code', 'LIKE', "%{$request->search_term}%");
                })
                ->select('id', 'code', 'first_name', 'last_name', 'middle_name', 'address')
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

            return response()->json(['success' => true, 'members' => $members]);

        } catch (\Exception $e) {
            Log::error('Member search error', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

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
            Log::warning('Failed verification - share account', [
                'member_code' => $member->code,
                'ip'          => $request->ip(),
            ]);

            return back()->withInput($preservedInput)
                ->withErrors(['share_account_last4' => 'The last 4 digits do not match our records. Please try again.']);
        }

        // Verify at least one security question passes
        if (!$this->verifySecurityQuestions($request, $member)) {
            Log::warning('Failed verification - security questions', [
                'member_code' => $member->code,
                'fields'      => ['middle_name' => $request->filled('middle_name'), 'birth_date' => $request->filled('birth_date')],
                'ip'          => $request->ip(),
            ]);

            return back()->withInput($preservedInput)
                ->withErrors(['verification' => 'The information provided does not match our records. Please check and try again.']);
        }

        // Check if already voted
        if ($this->memberHasVoted($member)) {
            $this->clearFullSession();
            return redirect()->route('voting.already-voted');
        }

        // Mark session as verified
        Session::put(self::SESSION_VERIFIED, true);
        Session::put(self::SESSION_VERIFIED_AT, now());

        Log::info('Member verification successful', ['member_code' => $member->code, 'ip' => $request->ip()]);

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
            'address'        => 'required|string|max:255',
            'email'          => 'nullable|email|max:100',
            'contact_number' => 'required|string|max:20',
            'occupation'     => 'nullable|string|max:100',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'religion'       => 'nullable|string|max:100',
        ]);

        try {
            $member = $this->getSessionMember();

            $member->update([
                'address'           => $request->address,
                'email'             => $request->email,
                'contact_number'    => $request->contact_number,
                'occupation'        => $request->occupation,
                'marital_status'    => $request->marital_status,
                'religion'          => $request->religion,
                'is_registered'     => true,
                'registration_type' => 'Online',
            ]);

            Log::info('Member information updated', ['member_code' => $member->code, 'ip' => $request->ip()]);

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
            Log::error('Update info: Member not found', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'Member not found. Please start again.']);

        } catch (\Exception $e) {
            Log::error('Update info error', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

            return back()->withInput()
                ->withErrors(['error' => 'Failed to update information. Please try again.']);
        }
    }

    // ─── Not Qualified Page ───────────────────────────────────────────────────

    public function notQualified()
    {
        // Must have a reason flashed from checkVotingEligibility
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
                Log::warning('No active positions available', ['member_code' => $member->code, 'ip' => request()->ip()]);

                return redirect()->route('voting.select-branch')
                    ->with('warning', 'No active positions available for voting at this time. Please contact the administrator.');
            }

            Session::put(self::SESSION_MEMBER_CODE, $member->code);
            Session::put(self::SESSION_BRANCH_NUMBER, $member->branch_number);

            Log::info('Member accessed ballot', [
                'member_code'       => $member->code,
                'positions_count'   => $positions->count(),
                'total_candidates'  => $positions->sum(fn($p) => $p->candidates->count()),
                'ip'                => request()->ip(),
            ]);

            return view('voting.ballot', compact('member', 'positions'));

        } catch (ModelNotFoundException $e) {
            Log::error('Ballot: Member or branch not found', ['error' => $e->getMessage(), 'ip' => request()->ip()]);
            Session::flush();

            return redirect()->route('voting.select-branch')
                ->withErrors(['error' => 'Member or branch not found. Please start again.']);

        } catch (\Exception $e) {
            Log::error('Ballot access error', ['error' => $e->getMessage(), 'ip' => request()->ip()]);

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
                $this->clearFullSession();

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

            Log::info('Votes submitted successfully', [
                'member_code'    => $member->code,
                'branch_number'  => $member->branch_number,
                'control_number' => $controlNumber,
                'total_votes'    => count($votesData),
                'ip'             => $request->ip(),
            ]);

            Session::put(self::SESSION_CONTROL_NUM, $controlNumber);
            Session::put(self::SESSION_VOTES_DONE, true);

            return redirect()->route('voting.confirmation');

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Vote submission error', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

            return back()->withErrors(['error' => 'Failed to submit votes. Please try again.']);
        }
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
            Log::error('Confirmation error', ['error' => $e->getMessage(), 'ip' => request()->ip()]);

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

            Log::info('Receipt downloaded', ['member_code' => $member->code, 'control_number' => $controlNumber, 'ip' => request()->ip()]);

            $this->clearFullSession();

            return response()->download($pdfPath, "voting_receipt_{$controlNumber}.pdf")
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Receipt download error', ['error' => $e->getMessage(), 'ip' => request()->ip()]);

            return back()->withErrors(['error' => 'Failed to generate receipt. Please contact support.']);
        }
    }

    // ─── Already Voted Page ───────────────────────────────────────────────────

    public function alreadyVoted()
    {
        // Must have come from a valid voting flow with a known member
        $memberCode   = Session::get(self::SESSION_MEMBER_CODE);
        $branchNumber = Session::get(self::SESSION_BRANCH_NUMBER);

        if (!$memberCode || !$branchNumber) {
            return redirect()->route('voting.select-branch');
        }

        // Confirm the member genuinely has votes on record
        $hasVoted = Vote::where('member_code', $memberCode)
            ->where('branch_number', $branchNumber)
            ->exists();

        if (!$hasVoted) {
            return redirect()->route('voting.select-branch');
        }

        return view('voting.already-voted');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Resolve branch and member from session, with redirect on failure.
     */
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
            Log::error('Failed to resolve session branch/member', ['error' => $e->getMessage(), 'ip' => $request->ip()]);

            return [null, null];
        }
    }

    /**
     * Get the authenticated member from session.
     */
    private function getSessionMember(): Member
    {
        return Member::findOrFail(Session::get(self::SESSION_MEMBER_ID));
    }

    /**
     * Get the session branch.
     */
    private function getSessionBranch(): Branch
    {
        return Branch::findOrFail(Session::get(self::SESSION_BRANCH_ID));
    }

    /**
     * Check if member has already voted.
     */
    private function memberHasVoted(Member $member): bool
    {
        return Vote::where('member_code', $member->code)
            ->where('branch_number', $member->branch_number)
            ->exists();
    }

    /**
     * Verify security questions (middle name and/or birth date).
     * Returns true if at least one provided answer is correct.
     */
    private function verifySecurityQuestions(Request $request, Member $member): bool
    {
        if ($request->filled('middle_name') &&
            strtolower(trim($request->middle_name)) === strtolower(trim($member->middle_name))) {
            return true;
        }

        if ($request->filled('birth_date') &&
            $request->birth_date === $member->birth_date->format('Y-m-d')) {
            return true;
        }

        return false;
    }

    /**
     * Check all voting eligibility rules.
     * Returns a RedirectResponse if ineligible, or null if eligible.
     *
     * share_amount encoding:
     *   1 = share amount is >= PHP 3,000.00 (eligible)
     *   0 = share amount is <  PHP 3,000.00 (not eligible)
     */
    private function checkVotingEligibility(Member $member, string $ip): ?RedirectResponse
    {
        if (!$member->is_migs) {
            Log::warning('Non-MIGS member attempted to vote', ['member_code' => $member->code, 'ip' => $ip]);

            $this->clearFullSession();

            return redirect()->route('voting.not-qualified')
                ->with('reason', 'Only MIGS (Member in Good Standing) can participate in voting.');
        }

        // share_amount: 1 = >= PHP 3,000 (eligible), 0/null = below PHP 3,000 (ineligible)
        if (!$member->share_amount) {
            Log::warning('Member with insufficient shares attempted to vote', [
                'member_code'  => $member->code,
                'share_amount' => $member->share_amount,
                'ip'           => $ip,
            ]);

            $this->clearFullSession();

            return redirect()->route('voting.not-qualified')
                ->with('reason', 'Your share amount must be greater than ₱3,000.00 to participate in voting.');
        }

        if ($this->memberHasVoted($member)) {
            Log::info('Already-voted member attempted to proceed', ['member_code' => $member->code, 'ip' => $ip]);

            $this->clearFullSession();

            return redirect()->route('voting.already-voted');
        }

        return null;
    }

    /**
     * Validate vote submission and build flat votes array.
     */
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

    /**
     * Retrieve votes grouped by position title.
     */
    private function getGroupedVotes(Member $member, string $controlNumber)
    {
        return Vote::where('member_code', $member->code)
            ->where('branch_number', $member->branch_number)
            ->where('control_number', $controlNumber)
            ->with(['candidate.position'])
            ->get()
            ->groupBy('candidate.position.title');
    }

    /**
     * Generate the PDF receipt and return file path.
     */
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

        Log::info('PDF receipt generated', [
            'control_number' => $controlNumber,
            'member_code'    => $member->code,
        ]);

        return $filepath;
    }

    /**
     * Check if the current session is verified and not expired.
     */
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

    /**
     * Clear all voting session data (used after download).
     */
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
        ]);
    }
}
