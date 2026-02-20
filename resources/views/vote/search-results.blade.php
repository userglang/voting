@extends('layouts.app')

@section('content')

<h2>Select Your Name</h2>

@if($members->isEmpty())
    <p>No members found.</p>
@else
    @foreach($members as $member)
        <div style="border-bottom:1px solid #ddd; padding:10px 0;">
            <strong>{{ $member->full_name }}</strong>
            <form method="POST" action="{{ route('vote.verify') }}">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <button type="submit">Verify Identity</button>
            </form>
        </div>
    @endforeach

@endif

@endsection
