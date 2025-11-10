<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            background: #2563eb;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .date {
            font-size: 12px;
            opacity: 0.9;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-row {
            display: table-row;
        }
        .stat-cell {
            display: table-cell;
            padding: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .stat-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d1d5db;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .chart-placeholder {
            background: #f3f4f6;
            padding: 20px;
            text-align: center;
            border: 1px dashed #9ca3af;
            margin: 10px 0;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="date">Generated on {{ $generated_at->format('F d, Y \a\t h:i A') }}</div>
        @if(isset($data['period']) && is_array($data['period']))
            <div class="date">Period: {{ $data['period']['start'] }} to {{ $data['period']['end'] }}</div>
        @else
            <div class="date">Period: {{ $data['period'] }}</div>
        @endif
    </div>

    <!-- Overview Statistics -->
    <div class="section">
        <div class="section-title">Overview Statistics</div>
        <div class="stats-grid">
            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-label">Total Contracts</div>
                    <div class="stat-value">{{ number_format($data['overview']['total_contracts']) }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Total Value</div>
                    <div class="stat-value">${{ number_format($data['overview']['total_value'], 2) }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Average Value</div>
                    <div class="stat-value">${{ number_format($data['overview']['average_value'], 2) }}</div>
                </div>
            </div>
            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-label">Signature Rate</div>
                    <div class="stat-value">{{ number_format($data['overview']['signature_rate'], 2) }}%</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Signed Signatures</div>
                    <div class="stat-value">{{ $data['overview']['signed_signatures'] }} / {{ $data['overview']['total_signatures'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Status Breakdown</div>
                    <div class="stat-value" style="font-size: 10px;">
                        @foreach($data['overview']['status_breakdown'] as $status => $count)
                            <span class="badge badge-{{ $status === 'signed' ? 'success' : ($status === 'draft' ? 'warning' : 'danger') }}">
                                {{ ucfirst($status) }}: {{ $count }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Contracts by Value -->
    <div class="section">
        <div class="section-title">Top Contracts by Value</div>
        <table>
            <thead>
                <tr>
                    <th>Contract Number</th>
                    <th>Title</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_contracts'] as $contract)
                    <tr>
                        <td>{{ $contract['contract_number'] }}</td>
                        <td>{{ $contract['title'] }}</td>
                        <td>${{ number_format($contract['contract_value'], 2) }}</td>
                        <td>{{ ucfirst($contract['status']) }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract['created_at'])->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Template Performance -->
    <div class="section">
        <div class="section-title">Template Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>Total Created</th>
                    <th>Signed</th>
                    <th>Conversion Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['template_stats']['conversion_rates'] as $template)
                    <tr>
                        <td>{{ $template['template_name'] }}</td>
                        <td>{{ $template['total_created'] }}</td>
                        <td>{{ $template['signed'] }}</td>
                        <td>
                            <span class="badge badge-{{ $template['conversion_rate'] >= 70 ? 'success' : ($template['conversion_rate'] >= 50 ? 'warning' : 'danger') }}">
                                {{ number_format($template['conversion_rate'], 2) }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Expiring Contracts -->
    @if($data['expiring_contracts']['count'] > 0)
        <div class="section">
            <div class="section-title">Contracts Expiring Soon ({{ $data['expiring_contracts']['count'] }} contracts)</div>
            <p style="margin-bottom: 10px; font-size: 10px;">Total Value at Risk: ${{ number_format($data['expiring_contracts']['total_value_at_risk'], 2) }}</p>
            <table>
                <thead>
                    <tr>
                        <th>Contract Number</th>
                        <th>Title</th>
                        <th>Expires At</th>
                        <th>Days Until Expiration</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($data['expiring_contracts']['contracts'], 0, 10) as $contract)
                        <tr>
                            <td>{{ $contract['contract_number'] }}</td>
                            <td>{{ $contract['title'] }}</td>
                            <td>{{ $contract['expires_at'] }}</td>
                            <td>{{ $contract['days_until_expiration'] }} days</td>
                            <td>${{ number_format($contract['contract_value'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Monthly Revenue -->
    <div class="section">
        <div class="section-title">Monthly Revenue Trend</div>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['monthly_revenue'] as $month)
                    <tr>
                        <td>{{ $month['month'] }}</td>
                        <td>${{ number_format($month['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated by {{ config('app.name') }} | Page <span class="pagenum"></span>
    </div>
</body>
</html>
