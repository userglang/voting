@extends('layouts.app')

@section('content')

<h2 class="success">Vote Successfully Submitted</h2>

<p>Your vote has been recorded securely.</p>

<p>Receipt Token:</p>
<strong>{{ $token }}</strong>

<br><br>

<a href="{{ route('vote.receipt.pdf', $token) }}">
    <button>Download PDF Receipt</button>
</a>

@endsection
