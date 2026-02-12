<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Members Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .filters {
            background: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
        .filters td {
            font-size: 9px;
            padding: 2px;
        }
        .stats table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .stats td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }
        .number {
            font-size: 12px;
            font-weight: bold;
        }
        .label {
            font-size: 8px;
            color: #666;
        }
        table.members {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.members th, table.members td {
            border: 1px solid #ddd;
            padding: 4px;
        }
        table.members th {
            background: #eee;
        }
        .yes { color: #090; }
        .no { color: #900; }
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Members Report</h1>
    <p>Generated on {{ now()->format('M d, Y - g:i A') }}</p>
</div>

<div class="filters">
    <table width="100%">
        <tr>
            <td><strong>Branch:</strong> {{ $filters['branch_name'] }}</td>
            <td><strong>Status:</strong> {{ $filters['status_label'] }}</td>
        </tr>
        <tr>
            <td><strong>MIGS:</strong> {{ $filters['migs_label'] }}</td>
            <td><strong>Gender:</strong> {{ $filters['gender'] }}</td>
        </tr>
    </table>
</div>

<div class="stats">
    <table>
        <tr>
            <td><div class="number">{{ $totalMembers }}</div><div class="label">Total Members</div></td>
            <td><div class="number">{{ $totalMale }}</div><div class="label">Male</div></td>
            <td><div class="number">{{ $totalFemale }}</div><div class="label">Female</div></td>
        </tr>
        <tr>
            <td><div class="number">{{ $totalMigs }}</div><div class="label">MIGS</div></td>
            <td><div class="number">{{ $totalNonMigs }}</div><div class="label">Non-MIGS</div></td>
            <td><div class="number">{{ $registeredMigs }}</div><div class="label">Registered MIGS</div></td>
        </tr>
        <tr>
            <td><div class="number">{{ $registeredNonMigs }}</div><div class="label">Registered Non-MIGS</div></td>
            <td><div class="number">{{ $registeredMale }}</div><div class="label">Registered Male</div></td>
            <td><div class="number">{{ $registeredFemale }}</div><div class="label">Registered Female</div></td>
        </tr>
    </table>
</div>

<table class="members">
    <thead>
        <tr>
            <th>CID</th>
            <th>Name</th>
            <th>Branch</th>
            <th>Gender</th>
            <th>Age</th>
            <th>MIGS</th>
            <th>Registered</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($members as $member)
            <tr>
                <td>{{ $member->cid }}</td>
                <td>{{ $member->full_name }}</td>
                <td>{{ $member->branch?->branch_name ?? '-' }}</td>
                <td>{{ $member->gender }}</td>
                <td>{{ $member->age }}</td>
                <td class="{{ $member->is_migs ? 'yes' : 'no' }}">{{ $member->is_migs ? 'YES' : 'NO' }}</td>
                <td class="{{ $member->is_registered ? 'yes' : 'no' }}">{{ $member->is_registered ? 'YES' : 'NO' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <p>Total Records: {{ $members->count() }}</p>
    <p>© {{ now()->year }} Member Management System</p>
</div>

</body>
</html>
