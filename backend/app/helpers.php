<?php

use App\Models\Classification;
use App\Models\Item;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

function generateRandomString($length = 10) 
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getDateRangeForReports(int $storeId=null, Carbon $startDate, Carbon $endDate=null)
{
    if (!$storeId)
    {
        $startDate = $startDate->copy()->startOfDay();
    }
    else 
    {
        $store = Store::with(['state' => function (BelongsTo $query) {
            return $query->select('id', 'timezone');
        }])
        ->select('id', 'state_id')
        ->find($storeId);

        $timezone = $store->state->timezone;

        $startDate = $startDate
            ->copy()
            ->timezone($timezone)
            ->startOfDay();
    }
        
    if (is_null($endDate))
    {
        $endDate = $startDate->copy()->endOfDay();
    } 
    else if (!$storeId)
    {
        $endDate = $endDate->copy()->endOfDay();
    }
    else 
    {
        $endDate = $endDate->copy()->timezone($timezone)->endOfDay();
    }
    
    return [
        'start_date' => $startDate->timezone('UTC'), 
        'end_date' => $endDate->timezone('UTC')
    ];
}

function getClassification($item)
{
    return isset($item['classification_id'])
        ? Classification::withTrashed()->find($item['classification_id']) 
        : Item::withTrashed()->find($item['id'])->classification;
}

?>