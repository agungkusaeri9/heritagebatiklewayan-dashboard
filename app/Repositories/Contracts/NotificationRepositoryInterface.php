<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    /**
     * Get paginated notifications with relations, counts, and filters.
     */
    public function getPaginatedNotifications(
        int $perPage = 10,
        ?string $search = null,
        ?string $recipientType = null,
        ?string $readStatus = null,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator;

    /**
     * Find notification by ID with relations.
     */
    public function findById(int $id): ?Notification;

    /**
     * Create a notification record.
     */
    public function create(array $data): Notification;

    /**
     * Delete a notification.
     */
    public function delete(Notification $notification): bool;

    /**
     * Mark all recipients as read for a notification.
     */
    public function markAllAsRead(Notification $notification): int;

    /**
     * Mark notification as read for a specific user.
     */
    public function markAsReadForUser(Notification $notification, int $userId): bool;
}
