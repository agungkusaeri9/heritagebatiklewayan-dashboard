<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $recipientPivot = $this->recipients->first();

        return [
            'id' => $this->id,
            'user_id' => $recipientPivot ? (int) $recipientPivot->user_id : (int) $request->user()?->id,
            'title' => $this->title,
            'message' => $this->message,
            'recipient_type' => $this->recipient_type,
            'category' => $this->setting ? [
                'code' => $this->setting->code,
                'name' => $this->setting->name,
            ] : null,
            'is_read' => $recipientPivot ? (bool) $recipientPivot->is_read : false,
            'read_at' => $recipientPivot?->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
