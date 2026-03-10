<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    // API endpoint for TopNav dropdown search
    public function query(Request $request)
    {
        $q = $request->input('q', '');
        $type = $request->input('type'); // Optional: 'contract', 'user', 'log'

        if (empty($q)) {
            return response()->json([]);
        }

        $user = Auth::user();
        $isOfficerOrHead = $user->hasRole(['Officer', 'Head']);
        $isAdminOrHead = $user->hasRole(['Admin', 'Head']);

        $contracts = [];
        if ($isOfficerOrHead && (!$type || $type === 'contract')) {
            $cq = Contract::where(function ($query) use ($q) {
                $query->where('contract_name', 'ilike', "%{$q}%")
                    ->orWhere('contract_number', 'ilike', "%{$q}%")
                    ->orWhere('awarded_to', 'ilike', "%{$q}%");
            });
            if ($user->hasRole('Officer')) {
                $cq->where('created_by', $user->id);
            }
            $contracts = $cq->select('id', 'contract_name as name', 'contract_number as detail', 'status')->take(5)->get()->map(function ($c) {
                $c->type = 'contract';
                $c->url = "/contracts/{$c->id}";
                $c->icon = 'FileText';
                $c->context = 'Contracts Registry';
                return $c;
            });
        }

        $users = [];
        if ($isAdminOrHead && (!$type || $type === 'user')) {
            $users = User::where(function ($query) use ($q) {
                $query->where('name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%");
            })->select('id', 'name', 'email as detail')->take(5)->get()->map(function ($u) use ($user) {
                $u->type = 'user';
                $u->url = $user->hasRole('Admin') ? "/admin/users" : "/head/users";
                $u->icon = 'User';
                $u->context = 'Personnel Details';
                return $u;
            });
        }

        $logs = [];
        if ($isAdminOrHead && (!$type || $type === 'log')) {
            $logs = AuditLog::with('user:id,name')->where(function ($query) use ($q) {
                $query->where('action_type', 'ilike', "%{$q}%")
                    ->orWhere('ip_address', 'ilike', "%{$q}%")
                    ->orWhereHas('user', function ($qu) use ($q) {
                        $qu->where('name', 'ilike', "%{$q}%");
                    });
            })->take(5)->get()->map(function ($l) use ($user) {
                return [
                    'id' => $l->id,
                    'name' => $l->action_type,
                    'detail' => $l->user ? $l->user->name : 'System',
                    'type' => 'log',
                    'url' => $user->hasRole('Admin') ? "/admin/logs" : "/head/logs",
                    'icon' => 'History',
                    'context' => 'Audit Logs'
                ];
            });
        }

        $results = array_merge(
            $contracts instanceof \Illuminate\Support\Collection ? $contracts->toArray() : $contracts,
            $users instanceof \Illuminate\Support\Collection ? $users->toArray() : $users,
            $logs instanceof \Illuminate\Support\Collection ? $logs->toArray() : $logs
        );

        return response()->json($results);
    }

    // Full Search Results Page
    public function index(Request $request)
    {
        $q = $request->input('q', '');

        $user = Auth::user();
        $isOfficerOrHead = $user->hasRole(['Officer', 'Head']);
        $isAdminOrHead = $user->hasRole(['Admin', 'Head']);

        $contracts = [];
        if ($isOfficerOrHead && !empty($q)) {
            $cq = Contract::with(['creator', 'approver'])->where(function ($query) use ($q) {
                $query->where('contract_name', 'ilike', "%{$q}%")
                    ->orWhere('contract_number', 'ilike', "%{$q}%")
                    ->orWhere('awarded_to', 'ilike', "%{$q}%");
            });
            if ($user->hasRole('Officer')) {
                $cq->where('created_by', $user->id);
            }
            $contracts = $cq->take(20)->get();
        }

        $users = [];
        if ($isAdminOrHead && !empty($q)) {
            $users = User::with('roles')->where(function ($query) use ($q) {
                $query->where('name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%");
            })->take(20)->get();
        }

        $logs = [];
        if ($isAdminOrHead && !empty($q)) {
            $logs = AuditLog::with('user')->where(function ($query) use ($q) {
                $query->where('action_type', 'ilike', "%{$q}%")
                    ->orWhere('ip_address', 'ilike', "%{$q}%")
                    ->orWhereHas('user', function ($qu) use ($q) {
                        $qu->where('name', 'ilike', "%{$q}%");
                    });
            })->orderBy('created_at', 'desc')->take(20)->get();
        }

        return Inertia::render('Search/Index', [
            'query' => $q,
            'results' => [
                'contracts' => $contracts,
                'users' => $users,
                'logs' => $logs
            ]
        ]);
    }
}
