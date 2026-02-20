@extends('layouts.app')

@section('content')

<h2>Update Personal Information</h2>

<form method="POST" action="{{ route('vote.update') }}">
    @csrf

    <label>Address</label>
    <input type="text" name="address" value="{{ $member->address }}" required>

    <label>Email</label>
    <input type="email" name="email" value="{{ $member->email }}">

    <label>Contact Number</label>
    <input type="text" name="contact_number" value="{{ $member->contact_number }}">

    <button type="submit">Continue to Ballot</button>
</form>

@endsection
