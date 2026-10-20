<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->relationLoaded('customer') ? $this->customer : $this->customer()->first();
        $phone = $customer?->phone;
        $gender = $customer?->gender;
        $avatar = $customer?->avatar;

        $isComplete = ! empty($this->name) &&
                      ! empty($phone) &&
                      ! empty($gender);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $phone,
            'gender' => $gender,
            'avatar' => $avatar,
            'avatar_url' => $avatar ? (filter_var($avatar, FILTER_VALIDATE_URL) ? $avatar : url($avatar)) : null,
            'is_profile_complete' => $isComplete,
            'roles' => $this->relationLoaded('roles') ? $this->roles->pluck('name') : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
