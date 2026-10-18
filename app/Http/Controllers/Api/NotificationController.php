<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Notification\MarkNotificationReadRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications applicable to the authenticated user.
     * Includes notifications specifically addressed to this user,
     * as well as broadcast notifications (ALL_USERS, CUSTOMER).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $this->accessibleQuery($user)
            ->with([
                'setting',
                'recipients' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
            ->latest('id');

        if ($request->has('is_read')) {
            $isRead = $request->boolean('is_read');
            if ($isRead) {
                $query->whereHas('recipients', function ($r) use ($user) {
                    $r->where('user_id', $user->id)->where('is_read', true);
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->whereHas('recipients', function ($r) use ($user) {
                        $r->where('user_id', $user->id)->where('is_read', false);
                    })->orWhereDoesntHave('recipients', function ($r) use ($user) {
                        $r->where('user_id', $user->id);
                    });
                });
            }
        }

        $perPage = (int) $request->input('per_page', 10);
        $notifications = $query->paginate($perPage);

        return ApiResponse::paginate(
            paginator: $notifications,
            data: NotificationResource::collection($notifications)->resolve($request),
            message: 'Berhasil mengambil daftar notifikasi.',
        );
    }

    /**
     * Mark notifications as read: either all notifications or a specific subset of IDs.
     */
    public function markRead(MarkNotificationReadRequest $request): JsonResponse
    {
        $user = $request->user();
        $markAll = $request->boolean('all');
        $notificationIds = $request->input('notification_ids');

        if ($markAll || empty($notificationIds)) {
            $count = $this->markAllNotificationsAsReadForUser($user);

            return ApiResponse::success(
                data: [
                    'updated_count' => $count,
                ],
                message: "Semua ({$count}) notifikasi berhasil ditandai sebagai sudah dibaca."
            );
        }

        $notifications = $this->accessibleQuery($user)
            ->whereIn('id', $notificationIds)
            ->get();

        $now = now();
        foreach ($notifications as $notification) {
            NotificationRecipient::updateOrCreate(
                [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                ],
                [
                    'is_read' => true,
                    'read_at' => $now,
                ]
            );
        }

        $count = $notifications->count();

        return ApiResponse::success(
            data: [
                'updated_count' => $count,
                'notification_ids' => $notifications->pluck('id')->all(),
            ],
            message: "{$count} notifikasi berhasil ditandai sebagai sudah dibaca."
        );
    }

    /**
     * Mark all accessible notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->markAllNotificationsAsReadForUser($user);

        return ApiResponse::success(
            data: [
                'updated_count' => $count,
            ],
            message: "Semua ({$count}) notifikasi berhasil ditandai sebagai sudah dibaca."
        );
    }

    /**
     * Mark a single notification as read for the authenticated user.
     */
    public function markSingleRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = $this->accessibleQuery($user)->where('id', $id)->first();

        if (! $notification) {
            return ApiResponse::error(
                message: 'Notifikasi tidak ditemukan.',
                code: 404
            );
        }

        $recipient = NotificationRecipient::updateOrCreate(
            [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ],
            [
                'is_read' => true,
                'read_at' => now(),
            ]
        );

        return ApiResponse::success(
            data: [
                'notification_id' => $notification->id,
                'is_read' => true,
                'read_at' => $recipient->read_at?->toIso8601String(),
            ],
            message: 'Notifikasi berhasil ditandai sebagai sudah dibaca.'
        );
    }

    /**
     * Mark all accessible notifications as read for a given user.
     */
    protected function markAllNotificationsAsReadForUser(User $user): int
    {
        $notifications = $this->accessibleQuery($user)->get();
        $now = now();

        foreach ($notifications as $notification) {
            NotificationRecipient::updateOrCreate(
                [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                ],
                [
                    'is_read' => true,
                    'read_at' => $now,
                ]
            );
        }

        return $notifications->count();
    }

    /**
     * Base query for notifications accessible to the given user.
     */
    protected function accessibleQuery(User $user): Builder
    {
        $isCustomer = $user->customer()->exists() || $user->hasRole('customer', 'api') || $user->hasRole('customer', 'web');

        $allowedTypes = ['ALL_USERS'];
        if ($isCustomer) {
            $allowedTypes[] = 'CUSTOMER';
        }
        if ($user->hasRole('admin', 'web') || $user->hasRole('super admin', 'web')) {
            $allowedTypes[] = 'ADMIN';
        }

        return Notification::query()
            ->where(function ($q) use ($user, $allowedTypes) {
                // Targeted directly to this user
                $q->whereHas('recipients', function ($r) use ($user) {
                    $r->where('user_id', $user->id);
                })
                // Broadcast notifications
                    ->orWhereIn('recipient_type', $allowedTypes);
            });
    }
}
