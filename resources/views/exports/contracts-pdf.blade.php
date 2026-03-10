<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract Registry - {{ now()->format('Y-m-d') }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #1E3A8A; margin: 0; }
        .subtitle { font-size: 11px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; color: #1f2937; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .status { text-transform: uppercase; font-weight: bold; font-size: 8px; }
        .footer { margin-top: 30px; font-size: 9px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Contract Registry</h1>
        <div class="subtitle">Generated on {{ now()->format('Y-m-d H:i') }} by {{ Auth::user()->name ?? 'System' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Contract Number</th>
                <th>Contract Name</th>
                <th>Awarded To</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $contract)
            <tr>
                <td>{{ $contract->contract_number }}</td>
                <td style="font-weight: bold;">{{ $contract->contract_name }}</td>
                <td>{{ $contract->awarded_to }}</td>
                <td>
                    <span class="status">{{ str_replace('_', ' ', $contract->status) }}</span>
                </td>
                <td>{{ ucfirst($contract->priority_level) }}</td>
                <td>{{ $contract->expiry_date ? $contract->expiry_date->format('d M Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Confidential Document. Contract Management System - Institutional Ledger.
    </div>
</body>
</html>
