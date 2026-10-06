<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function getPaginatedNotifications(
        int $perPage = 10,
        ?string $search = null,
        ?string $recipientType = null,
        ?string $readStatus = null,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        $query = Notification::query()
            ->with(['setting', 'creator'])
            ->withCount([
                'recipients as recipients_count',
                'recipients as read_recipients_count' => function (Builder $q) {
                    $q->where('is_read', true);
                },
                'recipients as unread_recipients_count' => function (Builder $q) {
                    $q->where('is_read', false);
                },
            ])
            ->search($search)
            ->filterRecipientType($recipientType);

        if ($readStatus === 'all_read') {
            $query->whereHas('recipients')
                ->whereDoesntHave('recipients', function (Builder $q) {
                    $q->where('is_read', false);
                });
        } elseif ($readStatus === 'has_unread') {
            $query->whereHas('recipients', function (Builder $q) {
                $q->where('is_read', false);
            });
        }

        $allowedSortColumns = ['id', 'title', 'recipient_type', 'created_at'];
        if (! in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortColumn, $sortDirection)->paginate($perPage);
    }

    public function findById(int $id): ?Notification
    {
        return Notification::query()
            ->with([
                'setting',
                'creator',
                'recipients.user',
            ])
            ->withCount([
                'recipients as recipients_count',
                'recipients as read_recipients_count' => function (Builder $q) {
                    $q->where('is_read', true);
                },
                'recipients as unread_recipients_count' => function (Builder $q) {
                    $q->where('is_read', false);
                },
            ])
            ->find($id);
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    public function markAllAsRead(Notification $notification): int
    {
        return $notification->recipients()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function markAsReadForUser(Notification $notification, int $userId): bool
    {
        $recipient = $notification->recipients()->where('user_id', $userId)->first();
        if ($recipient) {
            return $recipient->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return false;
    }
}
