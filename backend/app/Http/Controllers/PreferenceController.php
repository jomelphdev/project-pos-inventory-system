<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreferenceUpdateRequest;
use App\Http\Resources\CheckoutStationResource;
use App\Http\Resources\PreferenceResource;
use App\Models\CheckoutStation;
use App\Models\Organization;
use App\Models\Preference;
use App\Services\PreferencesService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreferenceController extends Controller
{
    /**
     * @param Integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $request->merge([
            'select' => $request->query('select'),
            'with' => $request->query('with'),
            'append' => $request->query('append')
        ]);
        $request->validate([
            'select' => 'nullable|string',
            'with' => 'nullable|string',
            'append' => 'nullable|string',
        ]);

        try
        {
            $select = $request->select
                ? explode(',', $request->select)
                : null;
            $with = $request->with
                ? explode(',', $request->with)
                : null;
            $append = $request->append
                ? explode(',', $request->append)
                : null;
            
            $preference = new Preference;
            
            if ($with)
            {
                $preference = $preference->with($with);
            }
            if ($select)
            {
                $preference = $preference->select($select);
            }
            
            $preference = $preference->findOrFail($request->user()->preferences->id);
            
            if ($append)
            {
                $preference->append($append);
            }
        }
        catch (ModelNotFoundException $e)
        {
            return response()->error('Preferences do not exist.');
        }

        return response()->success(['preferences' => new PreferenceResource($preference)]);
    }

    public function getMerchantInfo(Request $request)
    {
        $merchantOrg = Organization::where('slug', $request->input('slug', ''))->first();

        if (!$merchantOrg)
        {
            return response()->error('Merchant does not exist.');
        }

        $preferences = $merchantOrg->preferences;

        return response()->success([
            'stores' => $preferences->stores,
            'classifications' => $preferences->classifications,
            'conditions' => $preferences->conditions
        ]);
    }

    public function updatePreference(PreferenceUpdateRequest $request)
    {
        $data = $request->validated();
        
        try
        {
            DB::beginTransaction();

            PreferencesService::updateOrCreatePreferences($request->user()->preferences->id, $data);
            
            DB::commit();
        }
        catch (QueryException $e)
        {
            DB::rollBack();
            $sqlErrorCode = $e->errorInfo[1];

            if ($sqlErrorCode == 1062 && $data['type'] == 'checkout_stations')
            {
                return response()->errorWithData(
                    'You can only assign a terminal to one checkout station.', 
                    [
                        'checkout_station' => new CheckoutStationResource(
                            CheckoutStation::withTrashed()->where('terminal', $data['update']['terminal'])->first()
                        )
                    ]
                );
            }

            return response()->error('Something went wrong while trying to create preference.');
        }

        return response()->success(['preferences' => new PreferenceResource($request->user()->preferences)]);
    }

    public function updateMultiple(Request $request)
    {
        $updates = $request->input('updates');

        try
        {
            DB::beginTransaction();

            PreferencesService::updateOrCreatePreferences($request->user()->preferences->id, $updates);

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to create/update preferences.');
        }

        return response()->success(['preferences' => new PreferenceResource($request->user()->preferences)]);
    }

    public function update(Request $request) 
    {
        try
        {
            $preferences = $request->user()->preferences;
            $preferences->fill($request->all());
            $preferences->save();
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to update preferences.');
        }
        

        return response()->success(['preferences' => new PreferenceResource($preferences)]);
    }

    public function seedDefaultPreferences(Request $request)
    {
        $type = $request->input("default_type");
        $storeIds = $request->user()->preferences->stores()->get()->pluck("id")->toArray();
        $preferenceId = $request->user()->preferences->id;

        try
        {
            DB::beginTransaction();


            switch ($type)
            {
                case "classifications":
                    $defaultOptions = [
                        [
                            "store_id" => $storeIds,
                            "key" => "is_ebt",
                            "value" => false
                        ],
                        [
                            "store_id" => $storeIds,
                            "key" => "is_taxed",
                            "value" => true
                        ]
                    ];
                    
                    $resultingPreferences = PreferencesService::updateOrCreatePreferences($preferenceId, [
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Home & Garden",
                                "discount" => 0,
                                "preference_options" => $defaultOptions
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Electronics",
                                "discount" => 0,
                                "preference_options" => $defaultOptions
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Grocery",
                                "discount" => 0,
                                "preference_options" => $defaultOptions
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Clothing",
                                "discount" => 0,
                                "preference_options" => $defaultOptions
                            ]
                        ],
                    ]);
    
                    break;
                case "conditions":
                    $resultingPreferences = PreferencesService::updateOrCreatePreferences($preferenceId, [
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "New",
                                "discount" => 0,
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Like New",
                                "discount" => 0,
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Used",
                                "discount" => 0,
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "Damaged",
                                "discount" => 0,
                            ]
                        ],
                    ]);
                    break;
                case "discounts":
                    $resultingPreferences = PreferencesService::updateOrCreatePreferences($preferenceId, [
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "10%",
                                "discount" => 10,
                            ]
                        ],
                        [
                            "type" => $type,
                            "update" => [
                                "name" => "25%",
                                "discount" => 25,
                            ]
                        ],
                    ]);
                    break;
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to seed default preferences.');
        }

        return response()->success(["new_preferences" => $resultingPreferences]);
    }
}
