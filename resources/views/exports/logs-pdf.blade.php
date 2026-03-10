<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>System Audit Logs - {{ now()->format('Y-m-d') }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #1E3A8A; margin: 0; }
        .subtitle { font-size: 12px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; color: #1f2937; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        td { font-family: monospace; }
        .footer { margin-top: 30px; font-size: 9px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">System Audit Logs</h1>
        <div class="subtitle">Generated on {{ now()->format('Y-m-d H:i') }} by {{ Auth::user()->name ?? 'System' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Identity</th>
                <th>Role</th>
                <th>Operation Type</th>
                <th>Affected Resource</th>
                <th>IP Address</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                <td>{{ $log->role ?? 'SYS' }}</td>
                <td>{{ strtoupper($log->action_type) }}</td>
                <td>
                    @if($log->model_affected)
                        {{ class_basename($log->model_affected) }} #{{ $log->model_id }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $log->ip_address }}</td>
                <td>
                    @if($log->action_type === 'login' && isset($log->new_values['user_agent']))
                        {{ Str::limit($log->new_values['user_agent'], 30) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Confidential System Output. For internal use by authorized personnel only.
    </div>
</body>
</html>
