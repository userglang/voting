<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voting Receipt</title>
</head>
<body>

<h2>Cooperative Voting Receipt</h2>

<p>Receipt Token: {{ $token }}</p>

<p>This certifies that your vote has been securely recorded.</p>

<p>Date: {{ now()->format('F d, Y h:i A') }}</p>

<hr>

<p>This document serves as proof of participation.</p>

</body>
</html>
