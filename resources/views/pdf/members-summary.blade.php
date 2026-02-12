<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Members Summary Report</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
        }
        .header h1 {
            margin: 0 0 3px 0;
            font-size: 16px;
            color: #000;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        .summary-table th,
        .summary-table td {
            padding: 6px 3px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .summary-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #000;
        }
        .summary-table .number {
            font-size: 10px;
            font-weight: bold;
            color: #2c5282;
        }
        .summary-table .label {
            font-size: 8px;
            color: #666;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th {
            background-color: #f0f0f0;
            color: #000;
            padding: 6px 3px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        .data-table td {
            padding: 5px 3px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 8px;
        }
        .total-row {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        .branch-name {
            text-align: left;
            font-weight: bold;
        }
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 7px;
            color: #666;
            line-height: 1.3;
        }
        .percentage {
            color: #2c5282;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Members Summary Report</h1>
        <p>Generated on {{ now()->format('M d, Y - g:i A') }}</p>
    </div>

    <!-- Summary Table -->
    <table class="summary-table">
        <tr>
            <th>Total Members</th>
            <th>Branches</th>
            <th>MIGS Members</th>
            <th>Registered</th>
            <th>Votes Cast</th>
            <th>Overall Quorum</th>
        </tr>
        <tr>
            <td>
                <div class="number">{{ number_format($totalMembers) }}</div>
                <div class="label">Members</div>
            </td>
            <td>
                <div class="number">{{ number_format($totalBranches) }}</div>
                <div class="label">Branches</div>
            </td>
            <td>
                <div class="number">{{ number_format($totalMigs) }}</div>
                <div class="label">Members</div>
            </td>
            <td>
                <div class="number">{{ number_format($totalRegistered) }}</div>
                <div class="label">Members</div>
            </td>
            <td>
                <div class="number">{{ number_format($totalVotes) }}</div>
                <div class="label">Votes</div>
            </td>
            <td>
                <div class="number">{{ number_format($overallQuorum, 1) }}%</div>
                <div class="label">Quorum</div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Branch</th>
                <th>Total<br>Members</th>
                <th>MIGS</th>
                <th>Non-MIGS</th>
                <th>Reg<br>MIGS</th>
                <th>Reg<br>Non-MIGS</th>
                <th>Quorum %</th>
                <th>Votes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $branch)
            <tr>
                <td class="branch-name">{{ $branch['branch_name'] }}</td>
                <td>{{ number_format($branch['total_members']) }}</td>
                <td>{{ number_format($branch['total_migs']) }}</td>
                <td>{{ number_format($branch['total_non_migs']) }}</td>
                <td>{{ number_format($branch['total_reg_migs']) }}</td>
                <td>{{ number_format($branch['total_reg_non_migs']) }}</td>
                <td class="percentage">{{ number_format($branch['quorum_percentage'], 1) }}%</td>
                <td>{{ number_format($branch['total_casted_votes']) }}</td>
            </tr>
            @endforeach

            <tr class="total-row">
                <td class="branch-name">GRAND TOTAL</td>
                <td>{{ number_format($totalMembers) }}</td>
                <td>{{ number_format($totalMigs) }}</td>
                <td>{{ number_format($totalNonMigs) }}</td>
                <td>{{ number_format($totalRegMigs) }}</td>
                <td>{{ number_format($totalRegNonMigs) }}</td>
                <td class="percentage">{{ number_format($overallQuorum, 1) }}%</td>
                <td>{{ number_format($totalVotes) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Summary: {{ number_format($totalBranches) }} branches • {{ number_format($totalMembers) }} members • {{ number_format($totalVotes) }} votes • {{ number_format($overallQuorum, 1) }}% quorum</p>
        <p>Quorum: (Registered MIGS / Total MIGS) × 100</p>
        <p>© {{ now()->year }} Member Management System</p>
    </div>
</body>
</html>
