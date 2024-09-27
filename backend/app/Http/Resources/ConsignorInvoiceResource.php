<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsignorInvoiceResource extends JsonResource
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
            'organization' => $this->whenLoaded('organization'),
            'consignor_id' => $this->consignor_id,
            'consignor' => $this->whenLoaded('consignor'),
            'consignor_name' => $this->whenAppended('consignor_name'),
            'amount_paid' => $this->amount_paid,
            'amount_collected' => $this->amount_collected,
            'created_at' => $this->created_at
        ];
    }
}
