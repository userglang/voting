@extends('layouts.app')

@section('content')

<h2>Cooperative Voting</h2>

<form method="POST" action="{{ route('vote.search') }}">
    @csrf

    <label>Select Branch</label>
    <select name="branch_number" required>
        <option value="">-- Select Branch --</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->branch_number }}">{{ $branch->branch_name }}</option>
        @endforeach
    </select>

    <label>Search Name</label>
    <input type="text" name="name" required placeholder="Enter first or last name">

    <button type="submit">Search Member</button>
</form>


@endsection
