<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Condition;
use App\Models\Item;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Preference;
use App\Models\Quantity;
use App\Models\ReceiptOption;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;

class PosOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(int $stores=3, int $ordersPerStore=3, int $itemsPerOrder=3, $oneUser=false)
    {
        $this->call([StateSeeder::class]);
        if ($oneUser == 'true')
        {
            $user = User::factory()->create();
            $preference = Preference::factory()->state(['owner_id' => $user->id]);           
            $state = ['preference_id' => $preference->id, 'user_id' => $user->id];
            $classification = Classification::factory()->state($state)->create();
            $condition = Condition::factory()->state($state)->create();
        }

        for ($i=0; $i<$stores; $i++)
        {
            if ($oneUser != 'true')
            {
                $user = User::factory()->create();
                $preference = Preference::factory()->state(['owner_id' => $user->id]);        
                $state = ['preference_id' => $preference->id, 'user_id' => $user->id];
                $classification = Classification::factory()->state($state)->create();
                $condition = Condition::factory()->state($state)->create();
            }

            $store = Store::factory()->for($user)->state(['state_id' => 50, 'receipt_option_id' => ReceiptOption::factory()->state(['user_id' => $user->id])->create()->id])->create();
            for ($x=0; $x<$ordersPerStore; $x++)
            {

                $posOrder = PosOrder::factory()->create([
                    'user_id' => $user->id,
                    'created_by' => $user->id,
                    'store_id' => $store->id,
                    'tax_rate' => $store->tax_rate
                ]);
                
                for ($y=0; $y<$itemsPerOrder; $y++)
                {
                    try
                    {
                        $item = Item::factory()->create([
                            'user_id' => $posOrder->user_id,
                            'created_by' => $posOrder->user_id,
                            'classification_id' => $classification->id,
                            'condition_id' => $condition->id
                        ]);
                        PosOrderItem::factory()->create([
                            'pos_order_id' => $posOrder->id,
                            'item_id' => $item->id,
                            'discount_id' => null,
                            'discount_amount' => null
                        ]);
                        Quantity::factory()->create([
                            'item_id' => $item->id,
                            'store_id' => $posOrder->store_id
                        ]);
                    }
                    catch (QueryException $e) {}
                }
            }
        }
    }
}
