<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public function notifyUser(User $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('NotificationService::notifyUser failed', [
                'user_id'           => $user->id,
                'notification_class' => get_class($notification),
                'error'             => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a notification to a collection of users.
     */
    public function notifyMany(Collection $users, Notification $notification): void
    {
        try {
            NotificationFacade::send($users, $notification);
        } catch (\Throwable $e) {
            Log::error('NotificationService::notifyMany failed', [
                'user_ids'           => $users->pluck('id')->toArray(),
                'notification_class' => get_class($notification),
                'error'              => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a notification to all users holding a specific role.
     *
     * Usage: $service->notifyRole('Head', new ContractSubmitted($contract));
     */
    public function notifyRole(string $role, Notification $notification): void
    {
        $users = User::whereHas('roles', fn ($q) => $q->where('name', $role))->get();

        if ($users->isEmpty()) {
            Log::info('NotificationService::notifyRole — no users found for role', ['role' => $role]);
            return;
        }

        $this->notifyMany($users, $notification);
    }

    /**
     * Send a notification to multiple roles simultaneously.
     *
     * Usage: $service->notifyRoles(['Head', 'Admin'], new ContractExpiring($contract));
     */
    public function notifyRoles(array $roles, Notification $notification): void
    {
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->distinct()
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->notifyMany($users, $notification);
    }

    /**
     * Mark a single notification as read.
     * Returns false if not found or not owned by this user.
     */
    public function markRead(string $notificationId, User $user): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for a user.
     * Returns the count of notifications marked.
     */
    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Get paginated notifications for a user (newest first).
     */
    public function getForUser(User $user, int $perPage = 20, ?string $filter = null): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        if ($filter && $filter !== 'all') {
            $query = match ($filter) {
                'unread'       => $user->unreadNotifications()->latest(),
                'contracts'    => $user->notifications()->whereRaw("data::jsonb->>'type' = 'contract'")->latest(),
                'clauses'      => $user->notifications()->whereRaw("data::jsonb->>'type' = 'clause'")->latest(),
                'installments' => $user->notifications()->whereRaw("data::jsonb->>'type' = 'installment'")->latest(),
                'system'       => $user->notifications()->whereRaw("data::jsonb->>'type' = 'system'")->latest(),
                default        => $query,
            };
        }

        return $query->paginate($perPage);
    }

    /**
     * Get the unread notification count for a user.
     */
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Delete a specific notification (only if owned by this user).
     */
    public function delete(string $notificationId, User $user): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->delete();
        return true;
    }

    /**
     * Get the 5 most recent notifications (read or unread) for the dropdown.
     */
    public function getRecentForDropdown(User $user, int $limit = 5): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => $this->formatForDropdown($n));
    }

    /**
     * Format a database notification for the frontend dropdown/API response.
     */
    public function formatForDropdown(object $notification): array
    {
        $data = $notification->data;
        return [
            'id'         => $notification->id,
            'type'       => $data['type']    ?? 'system',
            'subtype'    => $data['subtype'] ?? null,
            'title'      => $data['title']   ?? 'Notification',
            'message'    => $data['message'] ?? '',
            'icon'       => $data['icon']    ?? 'bell',
            'color'      => $data['color']   ?? 'gray',
            'url'        => $data['action_url'] ?? ($data['link'] ?? '/dashboard'),
            'priority'   => $data['priority'] ?? 'low',
            'read'       => !is_null($notification->read_at),
            'created_at' => $notification->created_at->diffForHumans(),
        ];
    }
}
