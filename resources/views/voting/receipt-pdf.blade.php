<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voting Receipt - {{ $control_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #333;
            padding: 20px 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a5490;
        }

        .header h1 {
            color: #1a5490;
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            color: #2c5282;
            font-size: 11pt;
            font-weight: normal;
            margin-top: 2px;
        }

        .control-number {
            background: #fff5f5;
            border: 2px solid #c53030;
            border-radius: 5px;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
        }

        .control-number .label {
            font-size: 9pt;
            color: #666;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .control-number .number {
            font-size: 16pt;
            color: #c53030;
            font-weight: bold;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }

        .two-column {
            display: table;
            width: 100%;
            margin: 10px 0;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 5px;
        }

        .section-title {
            color: #2c5282;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 5px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-table {
            width: 100%;
            margin: 5px 0;
        }

        .info-table td {
            padding: 2px 5px;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 35%;
            color: #4a5568;
        }

        .info-table td:last-child {
            width: 65%;
            color: #2d3748;
        }

        .summary-inline {
            background: #ebf8ff;
            border: 1px solid #4299e1;
            border-radius: 4px;
            padding: 6px 10px;
            margin: 8px 0;
            text-align: center;
            font-size: 8.5pt;
        }

        .summary-inline .label {
            color: #2c5282;
            display: inline;
            margin-right: 8px;
        }

        .summary-inline .value {
            font-size: 12pt;
            font-weight: bold;
            color: #2b6cb0;
            display: inline;
        }

        .votes-compact {
            margin-top: 8px;
        }

        .position-block {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .position-title {
            background: #edf2f7;
            padding: 4px 8px;
            font-weight: bold;
            color: #2c5282;
            font-size: 9pt;
            border-left: 3px solid #4299e1;
            margin-bottom: 3px;
        }

        .candidate-list {
            padding-left: 15px;
        }

        .candidate-item {
            padding: 3px 8px;
            margin: 2px 0;
            background: #f7fafc;
            border-left: 2px solid #cbd5e0;
            font-size: 8.5pt;
        }

        .candidate-number {
            font-weight: bold;
            color: #4a5568;
            margin-right: 6px;
        }

        .candidate-name {
            color: #2d3748;
        }

        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #718096;
            line-height: 1.5;
        }

        .footer .important {
            color: #c53030;
            font-weight: bold;
            margin: 5px 0;
            font-size: 8pt;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60pt;
            color: rgba(26, 84, 144, 0.03);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }

        /* Ensure everything fits on one page */
        @page {
            margin: 0.4in;
            size: letter portrait;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    {{-- Watermark --}}
    <div class="watermark">OFFICIAL RECEIPT</div>

    {{-- Header --}}
    <div class="header">
        <h1>Cooperative Voting System</h1>
        <h2>Official Voting Receipt</h2>
    </div>

    {{-- Control Number --}}
    <div class="control-number">
        <div class="label">Control Number</div>
        <div class="number">{{ $control_number }}</div>
    </div>

    {{-- Member Info and Summary in Two Columns --}}
    <div class="two-column">
        <div class="column">
            <div class="section-title">Member Information</div>
            <table class="info-table">
                <tr>
                    <td>Member Code:</td>
                    <td>{{ $member->code }}</td>
                </tr>
                <tr>
                    <td>Name:</td>
                    <td>{{ strtoupper($member->full_name) }}</td>
                </tr>
                <tr>
                    <td>Branch:</td>
                    <td>{{ $branch->branch_name }}</td>
                </tr>
            </table>
        </div>
        <div class="column">
            <div class="section-title">Transaction Details</div>
            <table class="info-table">
                <tr>
                    <td>Date:</td>
                    <td>{{ $date }}</td>
                </tr>
                <tr>
                    <td>Time:</td>
                    <td>{{ $time }}</td>
                </tr>
                <tr>
                    <td>Total Votes:</td>
                    <td><strong>{{ $total_votes }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Votes Cast - Compact Layout --}}
    <div class="votes-compact">
        <div class="section-title">Votes Cast</div>

        @foreach($votes as $positionTitle => $candidates)
            <div class="position-block">
                <div class="position-title">{{ $positionTitle }}</div>
                <div class="candidate-list">
                    @foreach($candidates as $index => $candidateName)
                        <div class="candidate-item">
                            <span class="candidate-number">{{ $index + 1 }}.</span>
                            <span class="candidate-name">{{ $candidateName }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="important">
            This is an official voting receipt. Keep for your records.
        </div>
        <div>
            Control Number required for inquiries • Vote securely recorded • All votes are anonymous
        </div>
        <div style="margin-top: 5px; font-size: 6.5pt; color: #a0aec0;">
            Generated: {{ $date }} at {{ $time }} | System ID: {{ $control_number }}
        </div>
    </div>
</body>
</html>
