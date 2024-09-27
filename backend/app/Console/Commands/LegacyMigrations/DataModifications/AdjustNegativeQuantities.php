<?php

namespace App\Console\Commands\LegacyMigrations\DataModifications;

use App\Models\Item;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AdjustNegativeQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-modification:adj-neg-qty {user : MONGO_ID of admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adjusts all negative quantities to 0.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mongoId = $this->argument('user');
        $user = User::where('mongo_id', $mongoId)->first();
        $orgId = $user->organization->id;

        $qtyUpdates = [];

        Item::select('id', 'organization_id')->where('organization_id', $orgId)->chunk(200, function ($items) use (&$qtyUpdates, $user) {
            $items = $items->append('store_quantities');
            $items = $items->filter(function ($value, $key) {
                return count($value->store_quantities->where('quantity', '<', 0)) > 0;
            });

            foreach ($items as $i)
            {
                $updates = [];
                
                foreach ($i['store_quantities'] as $qty)
                {
                    if ($qty['quantity'] < 0) 
                    {
                        array_push($updates,
                            [
                                'store_id' => $qty['store_id'],
                                'created_by' => $user->id,
                                'quantity_received' => abs($qty['quantity']),
                                'message' => 'Adjust negative quantity to 0.'
                            ]
                        );
                    }
                }

                
                
                if (count($updates) > 0) 
                {
                    $itemUpdate = [
                        'item_id' => $i['id'],
                        'update' => [
                            'quantities' => $updates
                        ]
                    ];

                    array_push($qtyUpdates, $itemUpdate);
                }
            }

            var_dump(count($qtyUpdates));
        });

        $auth = Http::post(config('app.url') . '/api/users/authenticate', ['username' => $user->username, 'password' => 'Temp123']);

        foreach ($qtyUpdates as $update)
        {
            echo('Updating Item: ' . $update['item_id'] . "\n");
            $req = Http::withToken($auth['data']['user']['token'])->post(config('app.url') . '/api/items/update/' . strval($update['item_id']), $update['update']);
            $json = $req->json();

            if ($json['success'])
            {
                echo('Successfully updated Item: ' . $update['item_id'] . "\n");
                continue;
            }

            var_dump($json);
            echo('Failed to update Item: ' . $update['item_id'] . "\n");
        }
    }
}
