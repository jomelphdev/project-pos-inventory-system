<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuickBooksJournalEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $journalUrl = config('app.env') == 'production'
            ? 'https://app.qbo.intuit.com/app/journal?txnId='
            : 'https://app.sandbox.qbo.intuit.com/app/journal?txnId=';

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'quickbooks_journal_id' => $this->quickbooks_journal_id,
            'quickbooks_journal_url' => $journalUrl . $this->quickbooks_journal_id,
            'for_date' => $this->for_date->format('m/d/Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
