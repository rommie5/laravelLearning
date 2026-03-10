<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ClauseController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OtpVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────
// Guest Routes
// ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    // Rate-limited login (6 attempts per minute)
    Route::get('/', [LoginController::class, 'show'])->name('login');
    Route::post('/', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

// ──────────────────────────────────────────
// Authenticated Routes
// ──────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('otp/verify', [OtpVerificationController::class, 'show'])->name('otp.verify.show');
    Route::post('otp/verify', [OtpVerificationController::class, 'verify'])->name('otp.verify.submit');
    Route::post('otp/resend', [OtpVerificationController::class, 'resend'])->name('otp.verify.resend');

    Route::middleware('otp.verified')->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profile ──
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ── Notifications ──
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('api/notifications', [NotificationController::class, 'getRecent'])->name('notifications.recent');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // ── Global Search (Officer & Head only — Admin searches users, not contracts) ──
    Route::middleware('role:Officer|Head')->group(function () {
        Route::get('search', [SearchController::class, 'index'])->name('search.index');
        Route::get('api/search', [SearchController::class, 'query'])->name('search.query');
    });

    // ──────────────────────────────────────────
    // Contract Management — Officer & Head
    // ──────────────────────────────────────────
    Route::middleware('role:Officer|Head')->group(function () {
        Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('contracts/export/pdf', [ContractController::class, 'exportPdf'])->name('contracts.export.pdf');
        Route::get('contracts/export/excel', [ContractController::class, 'exportExcel'])->name('contracts.export.excel');
        Route::get('contracts/create', [ContractController::class, 'create'])->name('contracts.create');
        Route::post('contracts', [ContractController::class, 'store'])->name('contracts.store');
        Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
        Route::put('contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
        Route::post('contracts/{contract}/submit', [ContractController::class, 'submit'])->name('contracts.submit');
        Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
        Route::get('contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');

        // ── Clause lifecycle (Officer & Head) ──
        Route::post('clauses/{clause}/complete',            [ClauseController::class, 'markCompleted'])->name('clauses.complete');
        Route::post('clauses/{clause}/request-termination', [ClauseController::class, 'requestTermination'])->name('clauses.request-termination');

        // ── Installment actions (Officer & Head) ──
        Route::post('installments/{installment}/pay', [InstallmentController::class, 'markPaid'])->name('installments.pay');
    });

    Route::get('/test', function () {
    return "Laravel is working";
});

    // ──────────────────────────────────────────
    // Head-only Actions
    // ──────────────────────────────────────────
    Route::middleware('role:Head')->group(function () {
        // Approval workflow
        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
        Route::post('contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');

        // Update approval workflow
        Route::post('contracts/{contract}/updates/{update}/approve', [ContractController::class, 'approveUpdate'])->name('contracts.updates.approve');
        Route::post('contracts/{contract}/updates/{update}/reject', [ContractController::class, 'rejectUpdate'])->name('contracts.updates.reject');

        // Lifecycle actions
        Route::post('contracts/{contract}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
        Route::post('contracts/{contract}/close', [ContractController::class, 'close'])->name('contracts.close');

        // ── Clause lifecycle (Head-only actions) ──
        Route::post('clauses/{clause}/terminate', [ClauseController::class, 'terminate'])->name('clauses.terminate');
        Route::post('clauses/{clause}/override',  [ClauseController::class, 'overrideStatus'])->name('clauses.override');

        // Head-level logs and users (read-only views of admin data)
        Route::get('head/logs', [AuditLogController::class, 'index'])->name('head.logs.index');
        Route::get('head/logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('head.logs.export.pdf');
        Route::get('head/logs/export/excel', [AuditLogController::class, 'exportExcel'])->name('head.logs.export.excel');
        Route::get('head/users', [UserController::class, 'index'])->name('head.users.index');
        Route::get('head/users/export/pdf', [UserController::class, 'exportPdf'])->name('head.users.export.pdf');
        Route::get('head/users/export/excel', [UserController::class, 'exportExcel'])->name('head.users.export.excel');
    });

    // ──────────────────────────────────────────
    // Officer-only Actions
    // ──────────────────────────────────────────
    Route::middleware('role:Officer')->group(function () {
        Route::get('officer/logs', [AuditLogController::class, 'index'])->name('officer.logs.index');
        Route::get('officer/logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('officer.logs.export.pdf');
        Route::get('officer/logs/export/excel', [AuditLogController::class, 'exportExcel'])->name('officer.logs.export.excel');
    });

    // ──────────────────────────────────────────
    // Admin-only Routes (ZERO contract access)
    // ──────────────────────────────────────────
    Route::middleware('role:Admin')->group(function () {
        // User management — only index, store, update, destroy
        // (no create/show/edit pages — all handled via modal in Admin/Users.vue)
        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('admin/users/export/pdf', [UserController::class, 'exportPdf'])->name('admin.users.export.pdf');
        Route::get('admin/users/export/excel', [UserController::class, 'exportExcel'])->name('admin.users.export.excel');
        Route::post('admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('admin/users/{user}/force-logout', [UserController::class, 'forceLogout'])->name('admin.users.force-logout');

        // Audit logs
        Route::get('admin/logs', [AuditLogController::class, 'index'])->name('admin.logs.index');
        Route::get('admin/logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('admin.logs.export.pdf');
        Route::get('admin/logs/export/excel', [AuditLogController::class, 'exportExcel'])->name('admin.logs.export.excel');
    });

    });

});
