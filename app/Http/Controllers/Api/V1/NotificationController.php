<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\IndexNotificationRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $filter = $request->validated('filter', 'all');

        $query = $request->user()
            ->notifications()
            ->when($filter === 'unread', fn ($notificationQuery) => $notificationQuery->whereNull('read_at'))
            ->when($filter === 'read', fn ($notificationQuery) => $notificationQuery->whereNotNull('read_at'))
            ->latest();

        $notifications = $query->paginate(10);
        $payload = NotificationResource::collection($notifications)->response()->getData();

        return ApiResponse::success(
            'Lấy danh sách thông báo thành công.',
            [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'notifications' => $payload->data,
                'links' => $payload->links,
                'meta' => $payload->meta,
            ],
        );
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        /** @var DatabaseNotification|null $record */
        $record = $request->user()->notifications()->whereKey($notification)->first();

        if ($record === null) {
            return ApiResponse::error('Không tìm thấy thông báo.', status: 404);
        }

        $record->markAsRead();

        return ApiResponse::success(
            'Đã đánh dấu thông báo là đã đọc.',
            [
                'id' => $record->id,
                'read_at' => $record->fresh()?->read_at?->toISOString(),
            ],
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(
            'Đã đánh dấu tất cả thông báo là đã đọc.',
            ['unread_count' => 0],
        );
    }

    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () use ($request): void {
            $lastFingerprint = null;
            $startedAt = time();

            do {
                $payload = $this->streamPayload($request);
                $fingerprint = $payload['unread_count'].'|'.$payload['latest_notification_id'].'|'.$payload['latest_notification_read_at'];

                if ($fingerprint !== $lastFingerprint) {
                    echo "event: notifications\n";
                    echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n\n";

                    $lastFingerprint = $fingerprint;

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                }

                if ($request->boolean('once')) {
                    break;
                }

                sleep(2);
            } while (! connection_aborted() && time() - $startedAt < 60);
        }, 200, [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function streamPayload(Request $request): array
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(10)->get();
        $latest = $notifications->first();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'latest_notification_id' => $latest?->id,
            'latest_notification_read_at' => $latest?->read_at?->toISOString(),
            'notifications' => NotificationResource::collection($notifications)->resolve($request),
        ];
    }
}
