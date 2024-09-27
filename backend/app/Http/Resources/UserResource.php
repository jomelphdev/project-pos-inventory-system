<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->whenAppended('full_name'),
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'username' => $this->username,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'subscription_required' => $this->whenAppended('subscription_required'),
            'user_role' => $this->whenAppended('user_role'),
            'user_permissions' => $this->whenAppended('user_permissions'),
            'organization' => $this->whenLoaded('organization'),
            'preferences' => $this->whenLoaded('preferences'),
            'stores' => $this->whenLoaded('stores'),
            'user_feedback' => $this->whenLoaded('userFeedback'),
            'notifications' => $this->whenLoaded('notifications'),
            'unread_notifications' => $this->whenLoaded('unreadNotifications'),
            'token' => $this->when(isset($this->token), $this->token),
        ];
    }
}
