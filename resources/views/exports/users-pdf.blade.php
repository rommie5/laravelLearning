<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Registry - {{ now()->format('Y-m-d') }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #1E3A8A; margin: 0; }
        .subtitle { font-size: 12px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; color: #1f2937; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .footer { margin-top: 30px; font-size: 9px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">User Registry</h1>
        <div class="subtitle">Generated on {{ now()->format('Y-m-d H:i') }} by {{ Auth::user()->name ?? 'System' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role(s)</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td style="font-weight: bold;">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                <td>{{ $user->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Confidential Document. User Management System - Access Control List.
    </div>
</body>
</html>
