<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\NotificationSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected NotificationSettingService $settingService
    ) {}

    public function index(IndexNotificationRequest $request): Response
    {
        $notifications = $this->notificationService->getPaginatedNotifications(
            $request->integer('per_page', 10),
            $request->input('search'),
            $request->input('recipient_type'),
            $request->input('read_status'),
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return Inertia::render('notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'recipient_type', 'read_status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $settings = $this->settingService->getActiveSettings();
        $users = User::select(['id', 'name', 'email', 'username'])
            ->orderBy('name')
            ->get();

        return Inertia::render('notifications/Create', [
            'settings' => $settings,
            'users' => $users,
        ]);
    }

    public function store(StoreNotificationRequest $request): RedirectResponse
    {
        $notification = $this->notificationService->createNotification(
            $request->validated(),
            $request->user()?->id,
            $request->input('user_ids', [])
        );

        return redirect()->route('notifications.index')
            ->with('success', "Notifikasi '{$notification->title}' berhasil dibuat dan dikirimkan.");
    }

    public function show(Notification $notification): Response
    {
        $notificationWithDetails = $this->notificationService->getNotificationById($notification->id);

        abort_if(! $notificationWithDetails, 404);

        return Inertia::render('notifications/Show', [
            'notification' => $notificationWithDetails,
        ]);
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        $this->notificationService->deleteNotification($notification);

        return redirect()->route('notifications.index')
            ->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function markAllAsRead(Notification $notification): RedirectResponse
    {
        $count = $this->notificationService->markAllAsRead($notification);

        return redirect()->back()
            ->with('success', "{$count} penerima telah ditandai sudah membaca notifikasi.");
    }

    public function markAsRead(Notification $notification, Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;
        if ($userId) {
            $this->notificationService->markAsReadForUser($notification, $userId);
        }

        return redirect()->back()
            ->with('success', 'Notifikasi ditandai sebagai sudah dibaca.');
    }
}
