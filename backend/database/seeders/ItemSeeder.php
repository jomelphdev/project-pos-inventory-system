<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Condition;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Quantity;
use App\Models\ReceiptOption;
use App\Models\State;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()->create();

        Item::factory()
            ->create([
                'created_by' => $user->id,
                'user_id' => $user->id,
                'classification_id' => Classification::factory()->create([
                    'user_id' => $user->id
                ])->id,
                'condition_id' => Condition::factory()->create([
                    'user_id' => $user->id
                ])->id
            ])
            ->each(function (Item $item) {
                ItemImage::factory()
                    ->create([
                        'item_id' => $item->id, 
                        'image_url' => 'http://harvardhoodie.com/RetailRight/81860dbb259b410fa35ec0fc87efccfe.jpg'
                    ]);

                $state = State::factory()->create();
                Store::factory()
                    ->count(3)
                    ->create([
                        'user_id' => $item->id,
                        'state_id' => $state->id,
                        'receipt_option_id' => ReceiptOption::factory()->create([
                            'user_id' => $item->user_id
                        ])->id
                    ])
                    ->each(function (Store $store) use ($item) {
                        Quantity::factory()->create([
                            'item_id' => $item->user_id,
                            'store_id' => $store->id
                        ]);
                    });
            });
        
    }
}
