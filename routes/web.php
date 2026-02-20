<?php

use App\Http\Controllers\VotingController;
use App\Livewire\Voting\BranchSelection;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('vote')->name('vote.')->group(function () {
    Route::get('/start', BranchSelection::class)->name('start');
});



Route::prefix('voting')->name('voting.')->group(function () {

    // Step 1: Select branch
    Route::get('/', [VotingController::class, 'selectBranch'])
        ->name('select-branch');

    // Step 2: Search member (AJAX)
    Route::post('/search-member', [VotingController::class, 'searchMember'])
        ->name('search-member');
        // ->middleware('throttle:20,1'); // 20 requests per minute

    // Step 3: Show verification form (both POST and GET)
    Route::match(['get', 'post'], '/verify', [VotingController::class, 'showVerification'])
        ->name('show-verification');

    // Step 4: Verify identity (THROTTLED)
    Route::post('/verify/submit', [VotingController::class, 'verifyIdentity'])
        ->name('verify-identity');
        // ->middleware('throttle:3,1'); // 3 attempts per minute

    // Step 5: Update personal information
    Route::get('/update-info', [VotingController::class, 'showUpdateInfo'])
        ->name('update-info');

    Route::post('/update-info', [VotingController::class, 'updateInfo'])
        ->name('update-info.submit');

    // if non-migs or below 3k share amount
    Route::get('/not-qualified', [VotingController::class, 'notQualified'])
        ->name('not-qualified');

    // Step 6: Show ballot
    Route::get('/ballot', [VotingController::class, 'showBallot'])
        ->name('show-ballot');

    // Step 7: Submit votes
    Route::post('/submit', [VotingController::class, 'submitVotes'])
        ->name('submit-votes');
        // ->middleware('throttle:2,1'); // 2 submissions per minute (prevent double-click)

    // Step 8: Show confirmation
    Route::get('/confirmation', [VotingController::class, 'showConfirmation'])
        ->name('confirmation');

    // Step 9: Download receipt
    Route::get('/receipt/download', [VotingController::class, 'downloadReceipt'])
        ->name('download-receipt');

    // Already voted page
    Route::get('/already-voted', [VotingController::class, 'alreadyVoted'])
        ->name('already-voted');
});
