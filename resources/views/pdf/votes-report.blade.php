<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Votes Report</title>
    <style>
        @page {
            size: portrait;
            margin: 0.5in;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 15px;
            width: 100%;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        td {
            font-size: 12px;
        }

        .summary-table {
            margin-top: 20px;
            width: 100%;
            margin-bottom: 2%;
        }

        .summary-table th {
            background-color: #4A90E2;
            color: white;
        }

        .summary-table td {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        .summary-table .label {
            color: #888;
            font-size: 12px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            padding-top: 40px;
        }

        .signature-line {
            width: 80%;
            height: 1px;
            background-color: #333;
            margin: 0 auto 10px auto;
        }

        .signature-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div>
        <h1>Votes Report</h1>
        <p style="text-align: center;">Generated on {{ now()->format('F d, Y - g:i A') }}</p>
    </div>

    <!-- Filter Information -->
    <div>
        <table>
            <tr>
                <td><strong>Branch:</strong> {{ $filters['branch_name'] ?? 'All Branches' }}</td>
                <td><strong>Vote Type:</strong> {{ $filters['vote_type_label'] ?? 'All Types' }}</td>
            </tr>
            <tr>
                <td><strong>Date From:</strong> {{ $filters['date_from'] ?? 'N/A' }}</td>
                <td><strong>Date To:</strong> {{ $filters['date_to'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Summary Section as Table -->
    <table class="summary-table">
        <thead>
            <tr>
                <th>Total Votes</th>
                <th>Online Votes</th>
                <th>Offline Votes</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $votes->count() }}</td>
                <td>{{ $votes->where('online_vote', true)->count() }}</td>
                <td>{{ $votes->where('online_vote', false)->count() }}</td>
            </tr>
        </tbody>
    </table>



    <!-- Votes Data Table -->
    <table>
        <thead>
            <tr>
                <th>Control #</th>
                <th>Branch</th>
                <th>Member</th>
                <th>Candidate</th>
                <th>Position</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($votes as $vote)
                <tr>
                    <td>#{{ str_pad($vote->control_number, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $vote->branch?->branch_name ?? 'N/A' }}</td>
                    <td>{{ $vote->member?->full_name ?? 'N/A' }}</td>
                    <td>{{ $vote->candidate?->full_name ?? 'N/A' }}</td>
                    <td>{{ $vote->candidate?->position?->title ?? 'N/A' }}</td>
                    <td>
                        @if($vote->online_vote)
                            Online
                        @else
                            Offline
                        @endif
                    </td>
                    <td>{{ $vote->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">PREPARED BY</div>
        </div>

        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">NOTED BY</div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer">
        <p>This is a computer-generated report. {{ $votes->count() }} records.</p>
        <p>&copy; {{ now()->year }} Voting System. All rights reserved.</p>
    </div>
</body>
</html>
