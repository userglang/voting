<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Votes Summary Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            font-size: 12px;
        }
        h1, h2, h3 {
            color: #2c3e50;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e9e9e9;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 20px;
        }
        .no-data {
            text-align: center;
            color: #e74c3c;
            font-size: 16px;
            margin-top: 20px;
        }
        .summary-table {
            margin-top: 20px;
        }
        .summary-table td {
            font-weight: bold;
        }
        .vote-bar-wrap {
            background-color: #ecf0f1;
            border-radius: 4px;
            height: 15px;
            width: 100%;
        }
        .vote-bar {
            background-color: #2ecc71;
            height: 15px;
            border-radius: 4px;
            min-width: 0;
        }
        .zero-votes {
            color: #95a5a6;
            font-style: italic;
        }
        .leading-badge {
            background-color: #f39c12;
            color: white;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 4px;
        }
        .no-vote-row td {
            color: #95a5a6;
        }
        .datetime-label {
            color: #2c3e50;
        }
        .time-note {
            font-size: 10px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>

    <h1>Votes Summary Report</h1>
    <p>
        <strong>Generated on:</strong>
        <span class="datetime-label">{{ now()->format('F d, Y') }}</span>
        &mdash;
        <span class="datetime-label">{{ now()->format('g:i A') }}</span>
    </p>

    <h2>Filter Information</h2>
    <table>
        <tr>
            <td><strong>Branch:</strong> {{ $filters['branch_name'] ?? 'All Branches' }}</td>
            <td><strong>Vote Type:</strong> {{ $filters['vote_type_label'] ?? 'All Types' }}</td>
        </tr>
        <tr>
            <td>
                <strong>Date &amp; Time From:</strong>
                {{ $filters['date_from'] ?? 'Beginning' }}
            </td>
            <td>
                <strong>Date &amp; Time To:</strong>
                {{ $filters['date_to'] ?? 'Present' }}
            </td>
        </tr>
        <tr>
            <td colspan="2"><strong>Total Positions:</strong> {{ $summary->count() }}</td>
        </tr>
    </table>

    <h2>Summary Statistics</h2>
    <table class="summary-table">
        <tr>
            <td>Total Votes Cast</td>
            <td>{{ $totalVotes }}</td>
        </tr>
        <tr>
            <td>Total Candidates</td>
            <td>{{ $totalCandidates }}</td>
        </tr>
        <tr>
            <td>Online Votes</td>
            <td>{{ $totalOnlineVotes }}</td>
        </tr>
        <tr>
            <td>Offline Votes</td>
            <td>{{ $totalOfflineVotes }}</td>
        </tr>
    </table>

    @if($summary->isEmpty())
        <div class="no-data">
            📭 No voting data available for the selected filters.
        </div>
    @else
        @foreach($summary as $positionData)
            <h3>
                {{ $positionData['position_title'] }}
                @if($positionData['vacant_count'] > 1)
                    ({{ $positionData['vacant_count'] }} positions available)
                @endif
            </h3>
            <p>
                Total Votes: {{ $positionData['total_votes'] }} |
                Candidates: {{ count($positionData['candidates']) }}
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Candidate Name</th>
                        <th>Total Votes</th>
                        <th>Vote Breakdown</th>
                        <th>Vote Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($positionData['candidates'] as $index => $candidate)
                        <tr class="{{ $candidate['total'] === 0 ? 'no-vote-row' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $candidate['name'] }}
                                @if($index === 0 && $candidate['total'] > 0)
                                    <span class="leading-badge">Leading</span>
                                @endif
                            </td>
                            <td>
                                @if($candidate['total'] > 0)
                                    {{ $candidate['total'] }} ({{ $candidate['percentage'] }}%)
                                @else
                                    <span class="zero-votes">No votes yet</span>
                                @endif
                            </td>
                            <td>
                                @if($candidate['total'] > 0)
                                    Online: {{ $candidate['online'] }} | Offline: {{ $candidate['offline'] }}
                                @else
                                    <span class="zero-votes">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="vote-bar-wrap">
                                    <div class="vote-bar" style="width: {{ $candidate['percentage'] }}%;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        <p><strong>Summary:</strong> {{ $summary->count() }} positions | {{ $totalCandidates }} candidates | {{ $totalVotes }} total votes</p>
        <p>This report is generated automatically. All data is accurate as of {{ now()->format('F d, Y') }} at {{ now()->format('g:i A') }}.</p>
        <p>&copy; {{ now()->year }} Voting System. All rights reserved.</p>
    </div>

</body>
</html>
