@extends('layouts.app')

@section('content')

<h2>Official Ballot</h2>

<form method="POST" action="{{ route('vote.submit') }}">
    @csrf

    @foreach($positions as $position)
        <div style="margin-bottom:20px;">
            <h3>{{ $position->title }}</h3>
            <p>Vote up to {{ $position->vacant_count }}</p>

            @foreach($position->candidates as $candidate)
                <label>
                    <input type="checkbox"
                        name="votes[{{ $position->id }}][]"
                        value="{{ $candidate->id }}">
                    {{ $candidate->full_name }}
                </label><br>
            @endforeach
        </div>
    @endforeach

    <button type="submit"
        onclick="return confirm('Are you sure you want to submit your vote? This cannot be changed.')">
        Submit Vote
    </button>
</form>

@endsection
