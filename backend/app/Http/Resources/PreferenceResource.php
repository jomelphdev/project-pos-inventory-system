<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Permission;

class PreferenceResource extends JsonResource
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
            'owner' => $this->whenLoaded('owner'),
            'owner_id' => $this->owner_id,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'organization_id' => $this->organization_id,
            'classifications' => ClassificationResource::collection($this->whenLoaded('classifications')),
            'conditions' => ConditionResource::collection($this->whenLoaded('conditions')),
            'discounts' => DiscountResource::collection($this->whenLoaded('discounts', $this->discounts)),
            'checkout_stations' => CheckoutStationResource::collection($this->whenLoaded('checkoutStations')),
            'stores' => StoreResource::collection($this->whenLoaded('stores')),
            'employees' => $this->whenLoaded('employees'),
            'employees_with_permissions' => $this->whenAppended('employees_with_permissions'),
            'consignors' => $this->whenLoaded('consignors'),
            'states' => $this->when(isset($this->states), $this->states),
            'all_permissions' => Permission::getPermissionsArray(),
            'using_merchant_partner' => (bool) $this->using_merchant_partner,
            'merchant_username' => $this->merchant_username,
            'merchant_password' => $this->merchant_password,
            'merchant_id' => $this->merchant_id,
            'hide_pos_sales' => $this->hide_pos_sales,
            'classifications_disabled' => $this->classifications_disabled,
            'conditions_disabled' => $this->conditions_disabled,
            'version' => $this->version,
        ];
    }
}
