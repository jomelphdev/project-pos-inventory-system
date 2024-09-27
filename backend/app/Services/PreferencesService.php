<?php

namespace App\Services;

use App\Models\Preference;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PreferencesService
{
    public static function updateOrCreatePreferences(int $preferenceId, array $preferenceData)
    {
        $preferences = Preference::find($preferenceId);

        // If iterative array
        if (array_keys($preferenceData) === range(0, count($preferenceData) - 1))
        {
            $newPreferenceData = [];

            foreach ($preferenceData as $preference)
            {
                array_push($newPreferenceData, self::handlePreference($preferences, $preference));
            }

            return $newPreferenceData;
        }

        return self::handlePreference($preferences, $preferenceData);
    }

    public static function updateOrCreatePreferenceOptions($preference, array $options)
    {
        try
        {
            DB::beginTransaction();
            
            foreach ($options as $option)
            {
                $id = isset($option['id']) ? $option['id'] : null;

                if (is_array($option['store_id']))
                {
                    foreach ($option['store_id'] as $storeId)
                    {
                        $optionCopy = $option;
                        $optionCopy['store_id'] = $storeId;
                        $optionId = isset($option['model_id'])
                            ? $option['model_id']
                            : null;
                        
                        $preference->preferenceOptions()->updateOrCreate(['store_id' => $storeId, 'model_id' => $optionId], $optionCopy);
                    }

                    continue;
                }

                $preferenceOption = $preference->preferenceOptions()->findOrNew($id);
                $preferenceOption->fill($option);
                $preferenceOption->save();
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
        }
    }

    private static function handlePreference(Preference $preferences, $preferenceData)
    {
        try
        {
            $preference = null;
            $update = $preferenceData['update'];
            $type = $preferenceData['type'];
            $id = isset($update['id']) ? $update['id'] : null;

            switch ($type)
            {
                case 'classifications':
                    $preference = $preferences->classifications()->findOrNew($id);
                    break;
                case 'conditions':
                    $preference = $preferences->conditions()->findOrNew($id);
                    break;
                case 'discounts':
                    $preference = $preferences->discounts()->findOrNew($id);
                    break;
                case 'checkout_stations':
                    $preference = $preferences->checkoutStations()->findOrNew($id);

                    if (isset($update['drawer_balance']))
                    {
                        $update['last_balanced'] = now();
                    }
                    
                    break;
                case 'stores':
                    $preference = $preferences->stores()->findOrNew($id);
                    $preference->organization_id = $preferences->organization_id;

                    if (isset($update['receipt_option']))
                    {
                        $id = (isset($update['receipt_option']['id'])) ? $update['receipt_option']['id'] : null;
                        $receiptOption = $preferences->receiptOptions()->updateOrCreate(['id' => $id], $update['receipt_option']);
                        $preference->receipt_option_id = $receiptOption->id;
                    }

                    break;
                case 'consignors':
                    $preference = $preferences->consignors()->findOrNew($id);
                    break;
                case 'base':
                    $preference = $preferences;
                    break;
            }
        }
        catch (ModelNotFoundException $e)
        {
            throw $e;
        }

        if (isset($update['deleted_at']))
        {
            if ($update['deleted_at'] === false && !is_null($preference->deleted_at))
            {
                $preference->restore();
            }
            else if ($update['deleted_at'] != false)
            {
                $preference->delete();
            }

            unset($update['deleted_at']);
        }
        
        $preference->fill($update);
        $preference->save();

        if (isset($update['preference_options']) && count($update['preference_options']) > 0)
        {
            if ($preference->wasRecentlyCreated)
            {
                $options = collect($update['preference_options'])->pluck('store_id')->toArray();
                $optionIds = is_array($options[0]) 
                    ? $options[0]
                    : $options;
                $storeIds = $preferences->store_ids->toArray();
                $ids = array_merge(array_diff($optionIds, $storeIds), array_diff($storeIds, $optionIds));

                foreach ($ids as $id)
                {
                    self::updateOrCreatePreferenceOptions($preference, self::defaultClassificationOptions($id));
                }
            }

            self::updateOrCreatePreferenceOptions($preference, $update['preference_options']);
        }

        if ($type == "stores" && $preference->wasRecentlyCreated)
        {
            self::createClassificationOptionsForStore($preference->id, $preferences->classifications);
        }

        return $preference;
    }

    private static function createClassificationOptionsForStore(int $storeId, Collection $classifications)
    {
        foreach ($classifications as $classification)
        {
            PreferencesService::updateOrCreatePreferenceOptions($classification, self::defaultClassificationOptions($storeId));
        }
    }

    private static function defaultClassificationOptions(int $storeId)
    {
        return [
            [
                "store_id" => $storeId,
                "key" => "is_ebt",
                "value" => false
            ],
            [
                "store_id" => $storeId,
                "key" => "is_taxed",
                "value" => true
            ],
        ];
    }
}