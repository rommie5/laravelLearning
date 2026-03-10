<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserActionNotification;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected function getFilteredQuery(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', 1)->where('admin_force_logout', 0);
            } else {
                $query->where(function ($q) {
                    $q->where('is_active', 0)
                      ->orWhere('admin_force_logout', 1);
                });
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->getFilteredQuery($request);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->hasRole('Head')) {
            return Inertia::render('Head/Users', [
                'users' => $query->get(),
                'roles' => Role::all(),
                'filters' => $request->only(['search', 'role', 'status']),
            ]);
        }

        return Inertia::render('Admin/Users', [
            'users' => $query->get(),
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $users = $this->getFilteredQuery($request)->get();

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', 'PDF Generation Service Unavailable.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.users-pdf', compact('users'));

        AuditService::log('export_users_pdf', Auth::user());

        return $pdf->download('Users_Registry_' . now()->format('YmdHi') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $users = $this->getFilteredQuery($request)->get();

        $filename = "Users_Registry_" . now()->format('YmdHi') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Name', 'Email', 'Role', 'Status', 'Two Factor', 'Created At'];

        $callback = function() use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $u) {
                fputcsv($file, [
                    $u->name,
                    $u->email,
                    $u->roles->pluck('name')->implode(', '),
                    $u->is_active ? 'Active' : 'Inactive',
                    $u->two_factor_enabled ? 'Enabled' : 'Disabled',
                    $u->created_at,
                ]);
            }

            fclose($file);
        };

        AuditService::log('export_users_excel', Auth::user());

        return response()->stream($callback, 200, $headers);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'is_active' => 'required|boolean',
            'two_factor_enabled' => 'required|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'],
            'admin_force_logout' => false,
            'two_factor_enabled' => $validated['two_factor_enabled'],
            'otp_required' => true,
            'otp_verified_at' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
            'otp_expires_at' => null,
        ]);

        $user->assignRole($validated['role']);

        AuditService::log('create_user', $user);

        // Notify all admins about the new user
        $admins = User::role('Admin')->get();
        Notification::send($admins, new UserActionNotification([
            'subject' => 'New User Created',
            'message' => "A new user account for '{$user->name}' has been created.",
            'action_text' => 'View Users',
            'action_url' => url('/admin/users'),
        ]));

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|exists:roles,name',
            'is_active' => 'required|boolean',
            'two_factor_enabled' => 'required|boolean',
        ]);

        $user->update(array_merge($validated, [
            // Admin update for re-authorization when account is active.
            'admin_force_logout' => (bool) !$validated['is_active'],
        ]));
        $user->syncRoles([$validated['role']]);

        AuditService::log('update_user', $user);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Cannot delete last admin
        if ($user->hasRole('Admin') && User::role('Admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the last administrator.');
        }

        AuditService::log('delete_user', $user);
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function forceLogout(User $user)
    {
        $user->update([
            'admin_force_logout' => true,
            'remember_token' => null,
        ]);
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditService::log('force_logout', $user, null, ['target_user_id' => $user->id, 'target_user_email' => $user->email]);

        return back()->with('success', "{$user->name} has been logged out and blocked from logging in until re-authorization.");
    }
}
