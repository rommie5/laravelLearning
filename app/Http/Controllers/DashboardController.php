<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\AuditLog;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ──────────────────────────────────────────
        // ADMIN: system control only — zero contract data
        // ──────────────────────────────────────────
        if ($user->hasRole('Admin')) {
            $stats = [
                'total_users'    => User::count(),
                'active_users'   => User::where('is_active', true)->count(),
                'active_sessions' => DB::table('sessions')->count(),
                'failed_logins'  => AuditLog::where('action_type', 'failed_login')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ];

            $recent_logins = AuditLog::where('action_type', 'login')
                ->with('user')
                ->latest('created_at')
                ->take(8)
                ->get();

            $deactivated_users = User::where('is_active', false)
                ->with('roles')
                ->latest('updated_at')
                ->take(5)
                ->get();

            return Inertia::render('Dashboard', [
                'stats'             => $stats,
                'recent_logins'     => $recent_logins,
                'deactivated_users' => $deactivated_users,
            ]);
        }

        // ──────────────────────────────────────────
        // HEAD: full oversight — risk first
        // ──────────────────────────────────────────
        if ($user->hasRole('Head')) {
            $now = now();

            $stats = [
                'pending_approvals'    => Contract::where('status', Contract::STATUS_PENDING_APPROVAL)->count(),
                'expiring_14_days'     => Contract::where('status', Contract::STATUS_ACTIVE)
                    ->whereBetween('expiry_date', [$now, $now->copy()->addDays(14)])
                    ->count(),
                'expiring_31_days'     => Contract::where('status', Contract::STATUS_ACTIVE)
                    ->whereBetween('expiry_date', [$now, $now->copy()->addDays(31)])
                    ->count(),
                'active_contracts'     => Contract::where('status', Contract::STATUS_ACTIVE)->count(),
                'pending_updates'      => \App\Models\ContractUpdate::where('status', 'pending')->has('contract')->count(),
            ];

            $expiry_timeline = Contract::where('status', Contract::STATUS_ACTIVE)
                ->whereBetween('expiry_date', [$now, $now->copy()->addDays(60)])
                ->with('creator')
                ->orderBy('expiry_date')
                ->limit(10)
                ->get();

            $high_priority = Contract::where('status', Contract::STATUS_ACTIVE)
                ->whereIn('priority_level', ['high', 'sensitive'])
                ->with('creator')
                ->latest()
                ->limit(8)
                ->get();

            $recently_submitted = Contract::with('creator')
                ->where('status', Contract::STATUS_PENDING_APPROVAL)
                ->latest()
                ->limit(5)
                ->get();

            $recently_approved = Contract::with('approver')
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereNotNull('approved_at')
                ->latest('approved_at')
                ->limit(5)
                ->get();

            $pending_updates = \App\Models\ContractUpdate::with(['contract', 'requester'])
                ->has('contract')
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get();

            $recent_activity = AuditLog::with('user')
                ->latest('created_at')
                ->limit(10)
                ->get();

            return Inertia::render('Dashboard', compact(
                'stats',
                'expiry_timeline',
                'high_priority',
                'recently_submitted',
                'recently_approved',
                'pending_updates',
                'recent_activity'
            ));
        }

        // ──────────────────────────────────────────
        // OFFICER: action first — my contracts
        // ──────────────────────────────────────────
        if ($user->hasRole('Officer')) {
            $now = now();

            $stats = [
                'my_drafts'     => Contract::where('created_by', $user->id)->where('status', Contract::STATUS_DRAFT)->count(),
                'submitted'     => Contract::where('created_by', $user->id)->where('status', Contract::STATUS_PENDING_APPROVAL)->count(),
                'active'        => Contract::where('created_by', $user->id)->where('status', Contract::STATUS_ACTIVE)->count(),
                'expiring_soon' => Contract::where('created_by', $user->id)
                    ->where('status', Contract::STATUS_ACTIVE)
                    ->where('expiry_date', '<=', $now->copy()->addDays(31))
                    ->count(),
            ];

            $expiring_14 = Contract::where('created_by', $user->id)
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereBetween('expiry_date', [$now, $now->copy()->addDays(14)])
                ->orderBy('expiry_date')
                ->limit(10)
                ->get();

            $expiring_31 = Contract::where('created_by', $user->id)
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereBetween('expiry_date', [$now->copy()->addDays(15), $now->copy()->addDays(31)])
                ->orderBy('expiry_date')
                ->limit(10)
                ->get();

            $my_recent = Contract::where('created_by', $user->id)
                ->whereIn('status', [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_APPROVAL, Contract::STATUS_ACTIVE])
                ->latest()
                ->limit(8)
                ->get();

            $recently_submitted = Contract::where('created_by', $user->id)
                ->where('status', Contract::STATUS_PENDING_APPROVAL)
                ->latest()
                ->limit(5)
                ->get();

            return Inertia::render('Dashboard', compact(
                'stats',
                'expiring_14',
                'expiring_31',
                'my_recent',
                'recently_submitted'
            ));
        }

        return Inertia::render('Dashboard');
    }
}
