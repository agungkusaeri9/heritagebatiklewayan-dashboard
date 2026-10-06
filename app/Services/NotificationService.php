<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository,
        protected NotificationSettingRepositoryInterface $settingRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedNotifications(
        int $perPage = 10,
        ?string $search = null,
        ?string $recipientType = null,
        ?string $readStatus = null,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->notificationRepository->getPaginatedNotifications(
            $perPage,
            $search,
            $recipientType,
            $readStatus,
            $sortColumn,
            $sortDirection
        );
    }

    public function getNotificationById(int $id): ?Notification
    {
        return $this->notificationRepository->findById($id);
    }

    public function createNotification(array $data, ?int $createdBy = null, array $specificUserIds = []): Notification
    {
        return DB::transaction(function () use ($data, $createdBy, $specificUserIds) {
            $notificationData = [
                'notification_setting_id' => $data['notification_setting_id'] ?? null,
                'title' => $data['title'],
                'message' => $data['message'],
                'recipient_type' => $data['recipient_type'],
                'created_by' => $createdBy,
            ];

            $notification = $this->notificationRepository->create($notificationData);

            $targetUserIds = $this->resolveRecipientUserIds($data['recipient_type'], $specificUserIds);

            if (! empty($targetUserIds)) {
                $now = now();
                $rows = [];
                foreach (array_unique($targetUserIds) as $userId) {
                    $rows[] = [
                        'notification_id' => $notification->id,
                        'user_id' => $userId,
                        'is_read' => false,
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Chunked insert for efficiency
                foreach (array_chunk($rows, 500) as $chunk) {
                    NotificationRecipient::insert($chunk);
                }
            }

            $this->activityLogService->log(
                'create',
                "Membuat notifikasi baru '{$notification->title}' untuk target {$notification->recipient_type}",
                ['notification_id' => $notification->id]
            );

            return $notification;
        });
    }

    public function deleteNotification(Notification $notification): bool
    {
        return DB::transaction(function () use ($notification) {
            $title = $notification->title;
            $id = $notification->id;

            $deleted = $this->notificationRepository->delete($notification);

            if ($deleted) {
                $this->activityLogService->log(
                    'delete',
                    "Menghapus notifikasi '{$title}'",
                    ['notification_id' => $id]
                );
            }

            return $deleted;
        });
    }

    public function markAllAsRead(Notification $notification): int
    {
        $count = $this->notificationRepository->markAllAsRead($notification);

        $this->activityLogService->log(
            'update',
            "Menandai {$count} penerima sebagai sudah membaca notifikasi '{$notification->title}'",
            ['notification_id' => $notification->id]
        );

        return $count;
    }

    public function markAsReadForUser(Notification $notification, int $userId): bool
    {
        return $this->notificationRepository->markAsReadForUser($notification, $userId);
    }

    /**
     * Resolve user IDs based on recipient type.
     *
     * @return int[]
     */
    protected function resolveRecipientUserIds(string $recipientType, array $specificUserIds = []): array
    {
        return match ($recipientType) {
            'ALL_USERS' => User::pluck('id')->map(fn ($id) => (int) $id)->all(),
            'CUSTOMER' => User::where(function ($q) {
                $q->whereHas('customer')
                    ->orWhereHas('roles', fn ($roleQ) => $roleQ->where('name', 'customer'));
            })->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'ADMIN' => User::where(function ($q) {
                $q->whereHas('roles', fn ($roleQ) => $roleQ->whereIn('name', ['admin', 'super admin']))
                    ->orWhereDoesntHave('customer');
            })->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'SPECIFIC_USER' => array_map('intval', $specificUserIds),
            default => [],
        };
    }
}
