@extends('layouts.app')

@section('content')

<h2>Identity Verification</h2>

<form method="POST" action="{{ route('vote.verify') }}">
    @csrf

    <input type="hidden" name="member_id" value="{{ request('member_id') }}">

    <label>Last 4 digits of Share Account</label>
    <input type="password" name="share_last4" maxlength="4" required>

    @error('share_last4')
        <div class="error">{{ $message }}</div>
    @enderror

    <label>Middle Name (Optional)</label>
    <input type="text" name="middle_name">

    <label>OR Birth Date</label>
    <input type="date" name="birth_date">

    @error('verification')
        <div class="error">{{ $message }}</div>
    @enderror

    <button type="submit">Verify</button>
</form>

@endsection
