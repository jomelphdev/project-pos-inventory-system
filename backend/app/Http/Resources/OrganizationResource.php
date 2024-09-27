<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'pm_type' => $this->pm_type,
            'pm_last_four' => $this->pm_last_four,
            'subscription' => $this->whenLoaded('subscription'),
            'slug' => $this->slug,
            'is_quickbooks_in_use' => !!$this->quickbooks_realm_id,
            'is_quickbooks_authenticated' => $this->is_quickbooks_authenticated,
            'trial_ends_at' => $this->trial_ends_at,
            'items' => Item::collection($this->whenLoaded('items')),
            'preferences' => new PreferenceResource($this->whenLoaded('preferences')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'employees' => UserResource::collection($this->whenLoaded('employees')),
            'user_feedback' => $this->whenLoaded('userFeedback'),
            'quickbook_journals' => QuickBooksJournalEntryResource::collection($this->whenLoaded('quickBooksJournalEntries')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
