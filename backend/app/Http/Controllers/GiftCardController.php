<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\GiftCardStore;
use App\Models\GiftCardTopUp;
use Illuminate\Http\Request;
use App\Http\Resources\GiftCardResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GiftCardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $today = Carbon::today();
        $orgId = auth()->user()->organization_id;
    
        $gifts = GiftCard::with(['user', 'giftCardStore'])
            ->whereHas('user', function ($query) use ($orgId) {
                $query->where('organization_id', $orgId);
            })
            ->orderBy('id', 'desc')
            ->get();
    
        return response()->success(GiftCardResource::collection($gifts));
    }

    public function checkGiftCardBalance(Request $request)
    {
        $qrcode = $request->qrcode;
        $orgId = auth()->user()->organization_id;
    
        $gifts = GiftCard::where('gift_code', $qrcode)
            ->where('organization_id', $orgId)
            ->orderBy('id', 'desc')
            ->take(1)
            ->get();
    
        return response()->success(GiftCardResource::collection($gifts));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $userId = auth()->user()->id;
        $orgId = auth()->user()->organization_id;

        $data = $request->validate([
            'gift_code' => 'required|unique:gift_cards',
            'title' => 'required',
            'description' => 'required',
            'expiration_date' => 'required',
            'storeIds' => 'required'
        ], [
            'gift_code.unique' => 'The gift card code has already been taken.'
        ]);
        
        try {
            DB::beginTransaction();

            $giftCard = new GiftCard($data);
            $giftCard->created_by = $userId;
            $giftCard->organization_id = $orgId;
            $giftCard->save();

            $giftCardId = $giftCard->id;

            foreach ($data['storeIds'] as $storeId) {
                $giftCardStoreData = [
                    "gift_card_id" => $giftCardId,
                    "store_id" => $storeId,
                ];
                $giftCardStore = new GiftCardStore($giftCardStoreData);
                $giftCardStore->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->error("Something went wrong while trying to create gift card.");
        }
    
        return response()->success(['message' => 'Gift Card created successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function show(GiftCard $giftCard)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function edit(GiftCard $giftCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'expiration_date' => 'required',
            'storeIds' => 'required'
        ]);
    
        try {
            DB::beginTransaction();

            $giftCard = GiftCard::findOrFail($id);
            $giftCard->update($data);

            // check if there is changes in selected stores 
            if(!$request->matchedStoreIds) {
                // delete existing gift card store
                GiftCardStore::where('gift_card_id', $id)->delete();

                // add new gift card store
                foreach ($data['storeIds'] as $storeId) {
                    $giftCardStoreData = [
                        "gift_card_id" => $id,
                        "store_id" => $storeId,
                    ];
                    $giftCardStore = new GiftCardStore($giftCardStoreData);
                    $giftCardStore->save();
                }
            }      

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->error("Something went wrong while trying to update the gift card.");
        }
    
        return response()->success(['message' => 'Gift Card updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function destroy(GiftCard $giftCard)
    {
        //
    }

    public function activateDeactivate(Request $request, $id)
    {
        $data = $request->validate([
            'is_activated' => 'required',
        ]);

        $statusMessage = $data['is_activated'] == 0 ? "Gift card has been deactivated." : "Gift card has been activated.";
    
        try {
            $giftCard = GiftCard::findOrFail($id);
            $giftCard->update($data);     
        } catch (Exception $e) {
            return response()->error("Something went wrong while trying to update the gift card.");
        }
    
        return response()->success(['message' => $statusMessage]);
    }

    public function updateGiftCardBalance(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required',
        ]);
    
        try {
            DB::beginTransaction();

            $giftCard = GiftCard::findOrFail($id);
            $currentBalance = $giftCard->balance; 
            $newBalance = $currentBalance + $data['amount']; 
            $giftCard->balance = $newBalance; 
            $giftCard->save(); // update the gift card balance

            $topUpData = [
                'amount' => $data['amount'],
                'gift_card_id' => $request->giftId,
            ];

            $topUp = new GiftCardTopUp($topUpData);
            $topUp->save(); // save the gift card top up

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->error("Something went wrong while trying to update the gift card.");
        }
    
        return response()->success(['message' => 'Gift Card updated successfully']);
    }
}
