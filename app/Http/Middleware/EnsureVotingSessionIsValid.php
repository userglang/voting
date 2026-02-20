<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class EnsureVotingSessionIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if session has required voting data
        if (!Session::has('voting.branch_id') || !Session::has('voting.member_id')) {
            return redirect()->route('voting.select-branch')
                ->withErrors(['session' => 'Your session has expired. Please start again.']);
        }

        // Check if session is verified for protected routes
        if ($request->routeIs(['voting.update-info', 'voting.update-info.submit', 'voting.show-ballot', 'voting.submit-votes'])) {
            if (!Session::get('voting.verified')) {
                return redirect()->route('voting.select-branch')
                    ->withErrors(['session' => 'Please verify your identity first.']);
            }

            // Check session timeout (30 minutes)
            $verifiedAt = Session::get('voting.verified_at');
            if (!$verifiedAt || now()->diffInMinutes($verifiedAt) > 30) {
                Session::forget(['voting.branch_id', 'voting.member_id', 'voting.verified', 'voting.verified_at']);
                return redirect()->route('voting.select-branch')
                    ->withErrors(['session' => 'Your session has expired for security reasons. Please start again.']);
            }
        }

        return $next($request);
    }
}
