<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Branch, Member, Position, Candidate, Vote};
use Illuminate\Foundation\Testing\RefreshDatabase;

class VotingSystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_voting_flow()
    {
        // Setup
        $branch = Branch::factory()->create();
        $member = Member::factory()->create([
            'branch_number' => $branch->branch_number,
            'share_account' => '123456789',
            'middle_name' => 'Test',
        ]);
        $position = Position::factory()->create(['vacant_count' => 1]);
        $candidate = Candidate::factory()->create(['position_id' => $position->id]);

        // Step 1: Search
        $response = $this->postJson(route('voting.search-member'), [
            'branch_id' => $branch->id,
            'search_term' => $member->first_name,
        ]);
        $response->assertStatus(200);

        // Step 2: Verify
        $response = $this->withSession([
            'voting.branch_id' => $branch->id,
            'voting.member_id' => $member->id,
        ])->post(route('voting.verify-identity'), [
            'share_account_last4' => '6789',
            'middle_name' => 'Test',
        ]);
        $response->assertRedirect(route('voting.update-info'));

        // Step 3: Vote
        $response = $this->withSession([
            'voting.branch_id' => $branch->id,
            'voting.member_id' => $member->id,
            'voting.verified' => true,
            'voting.verified_at' => now(),
        ])->post(route('voting.submit-votes'), [
            'votes' => [
                $position->id => [$candidate->id]
            ]
        ]);
        $response->assertRedirect(route('voting.confirmation'));

        // Verify vote was recorded
        $this->assertDatabaseHas('votes', [
            'member_code' => $member->code,
            'candidate_id' => $candidate->id,
        ]);
    }
}
