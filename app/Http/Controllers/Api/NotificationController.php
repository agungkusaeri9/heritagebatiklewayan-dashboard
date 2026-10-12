<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
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
        $isCustomer = $user->customer()->exists() || $user->hasRole('customer', 'api') || $user->hasRole('customer', 'web');

        $allowedTypes = ['ALL_USERS'];
        if ($isCustomer) {
            $allowedTypes[] = 'CUSTOMER';
        }
        if ($user->hasRole('admin', 'web') || $user->hasRole('super admin', 'web')) {
            $allowedTypes[] = 'ADMIN';
        }

        $query = Notification::query()
            ->where(function ($q) use ($user, $allowedTypes) {
                // Notifications explicitly targeted to this user
                $q->whereHas('recipients', function ($r) use ($user) {
                    $r->where('user_id', $user->id);
                })
                // Broadcast notifications for all users or the user's role group
                    ->orWhereIn('recipient_type', $allowedTypes);
            })
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
}
