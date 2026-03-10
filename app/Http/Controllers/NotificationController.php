<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service) {}

    // ─────────────────────────────────────────
    // Inertia Page
    // ─────────────────────────────────────────

    /**
     * Full notifications page with filter tabs.
     * GET /notifications
     */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $filter = $request->query('filter', 'all');

        $notifications = $this->service->getForUser($user, 20, $filter);

        // Format each notification for the frontend
        $formatted = $notifications->through(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type']    ?? 'system',
            'subtype'    => $n->data['subtype']  ?? null,
            'title'      => $n->data['title']    ?? 'Notification',
            'message'    => $n->data['message']  ?? '',
            'icon'       => $n->data['icon']     ?? 'bell',
            'color'      => $n->data['color']    ?? 'gray',
            'priority'   => $n->data['priority'] ?? 'low',
            'action_url' => $n->data['action_url'] ?? ($n->data['link'] ?? '/dashboard'),
            'read'       => !is_null($n->read_at),
            'read_at'    => $n->read_at?->toISOString(),
            'created_at' => $n->created_at->diffForHumans(),
            'created_at_full' => $n->created_at->toDateTimeString(),
        ]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $formatted,
            'filter'        => $filter,
            'unreadCount'   => $this->service->unreadCount($user),
        ]);
    }

    // ─────────────────────────────────────────
    // JSON API Endpoints
    // ─────────────────────────────────────────

    /**
     * Dropdown data — last N notifications.
     * GET /notifications/data?limit=5
     */
    public function data(Request $request)
    {
        $user  = Auth::user();
        $limit = min((int) $request->query('limit', 5), 20);

        $recent = $this->service->getRecentForDropdown($user, $limit);

        return response()->json([
            'success'       => true,
            'count'         => $this->service->unreadCount($user),
            'notifications' => $recent->values(),
        ]);
    }

    /**
     * Lightweight badge-only count.
     * GET /notifications/unread-count
     */
    public function unreadCount()
    {
        return response()->json([
            'success' => true,
            'count'   => $this->service->unreadCount(Auth::user()),
        ]);
    }

    /**
     * Mark one notification read.
     * POST /notifications/{id}/read
     */
    public function markRead(string $id)
    {
        $user         = Auth::user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        // Security: ownership already guaranteed by scoping to $user->notifications()
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data'    => ['read_at' => now()->toISOString()],
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications read.
     * POST /notifications/read-all
     */
    public function markAllRead()
    {
        $count = $this->service->markAllRead(Auth::user());

        return response()->json([
            'success' => true,
            'data'    => ['marked' => $count],
            'message' => "{$count} notification(s) marked as read.",
        ]);
    }

    /**
     * Delete a single notification.
     * DELETE /notifications/{id}
     */
    public function destroy(string $id)
    {
        $user         = Auth::user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    // ─────────────────────────────────────────
    // Legacy endpoint (kept for backwards compat with TopNav until migrated)
    // ─────────────────────────────────────────

    /**
     * @deprecated Use GET /notifications/data instead
     * GET /api/notifications
     */
    public function getRecent()
    {
        return $this->data(request());
    }
}
