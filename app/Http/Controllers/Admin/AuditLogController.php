<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    protected function getFilteredQuery(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action_type', 'ilike', "%{$search}%")
                  ->orWhere('ip_address', 'ilike', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        return $query->latest();
    }

    public function index(Request $request)
    {
        $query = $this->getFilteredQuery($request);

        $user = \Illuminate\Support\Facades\Auth::user();
        $view = match(true) {
            $user->hasRole('Admin')   => 'Admin/Logs',
            $user->hasRole('Head')    => 'Head/Logs',
            default                   => 'Officer/Logs',
        };

        return Inertia::render($view, [
            'logs'    => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $logs = $this->getFilteredQuery($request)->get();

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', 'PDF Generation Service Unavailable.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.logs-pdf', compact('logs'));
        
        \App\Services\AuditService::log('export_logs_pdf', \Illuminate\Support\Facades\Auth::user());
        
        return $pdf->download('Audit_Logs_' . now()->format('YmdHi') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $logs = $this->getFilteredQuery($request)->get();
        
        $filename = "Audit_Logs_" . now()->format('YmdHi') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Timestamp', 'Identity', 'Role', 'Operation Type', 'Affected Resource', 'IP Address'];

        $callback = function() use ($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at,
                    $log->user ? $log->user->name : 'SYSTEM',
                    $log->role ?? 'SYS',
                    strtoupper($log->action_type),
                    $log->model_affected ? class_basename($log->model_affected) . " #{$log->model_id}" : 'N/A',
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        \App\Services\AuditService::log('export_logs_excel', \Illuminate\Support\Facades\Auth::user());

        return response()->stream($callback, 200, $headers);
    }

}
