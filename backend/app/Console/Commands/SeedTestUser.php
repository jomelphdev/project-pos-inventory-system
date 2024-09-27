<?php

namespace App\Console\Commands;

use App\Models\ReceiptOption;
use App\Models\State;
use App\Models\User;
use App\Services\PreferencesService;
use App\Services\UserService;
use Illuminate\Console\Command;

class SeedTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'e2e:seed-test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds all data to complete E2E front end tests.';

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
        if ($testUser = User::where("username", "TestUser")->first())
        {
            $testUser->organization()->forceDelete();
        }
        
        $user = UserService::createUser([
            "first_name" => "Test",
            "last_name" => "User",
            "email" => "testuser@test.com",
            "username" => "TestUser",
            "password" => "password",
            "email_verified_at" => now()
        ]);

        $receiptOption = ReceiptOption::create([
            "preference_id" => $user->preferences->id,
            'name' => "Shoppe Right",
            'footer' => "Thanks for shopping with us!",
        ]);

        $stateId = State::where("abbreviation", "AZ")->first()->id;

        $stores = [
            [
                "organization_id" => $user->organization_id,
                "receipt_option_id" => $receiptOption->id,
                "state_id" => $stateId,
                "city" => "Testopia",
                "address" => "123 Testing St",
                "zip" => 85374,
                "name" => "Shoppe Right - Testopia",
                'phone' => "9998889999",
                "tax_rate" => .075
            ],
            [
                "organization_id" => $user->organization_id,
                "receipt_option_id" => $receiptOption->id,
                "state_id" => $stateId,
                "city" => "Unit City",
                "address" => "987 Unit Dr",
                "zip" => 85388,
                "name" => "Shoppe Right - Unit Falls",
                'phone' => "9998889999",
                "tax_rate" => .075
            ],
            [
                "organization_id" => $user->organization_id,
                "receipt_option_id" => $receiptOption->id,
                "state_id" => $stateId,
                "city" => "Cypress Falls",
                "address" => "456 Testing Tr",
                "zip" => 23454,
                "name" => "Shoppe Right - Cypress",
                'phone' => "9998889999",
                "tax_rate" => .075
            ],
        ];

        $user->preferences->stores()->createMany($stores);
        $storeIds = $user->preferences->stores()->get()->pluck('id')->toArray();

        $preferenceOptions = [
            [
                "type" => "classifications",
                "update" => [
                    "name" => "Appliances",
                    "discount" => 20,
                    "preference_options" => [
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
                    ]
                ]
            ],
            [
                "type" => "classifications",
                "update" => [
                    "name" => "Grocery",
                    "discount" => 15,
                    "preference_options" => [
                        [
                            "store_id" => $storeIds,
                            "key" => "is_ebt",
                            "value" => true
                        ],
                        [
                            "store_id" => $storeIds,
                            "key" => "is_taxed",
                            "value" => false
                        ]
                    ]
                ]
            ],
            [
                "type" => "classifications",
                "update" => [
                    "name" => "Garden",
                    "discount" => 10,
                    "preference_options" => [
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
                    ]
                ]
            ],
            [
                "type" => "classifications",
                "update" => [
                    "name" => "Furniture",
                    "discount" => 5,
                    "preference_options" => [
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
                    ]
                ]
            ],
            [
                "type" => "conditions",
                "update" => [
                    "name" => "New",
                    "discount" => 20,
                ]
            ],
            [
                "type" => "conditions",
                "update" => [
                    "name" => "Like New",
                    "discount" => 15,
                ]
            ],
            [
                "type" => "conditions",
                "update" => [
                    "name" => "Used",
                    "discount" => 10,
                ]
            ],
            [
                "type" => "conditions",
                "update" => [
                    "name" => "Damaged",
                    "discount" => 5,
                ]
            ],
            [
                "type" => "discounts",
                "update" => [
                    "name" => "That button you press when your friend comes in.",
                    "discount" => 20,
                ]
            ]
        ];
        
        PreferencesService::updateOrCreatePreferences($user->preferences->id, $preferenceOptions);
        $user->preferences->using_merchant_partner = true;
        $user->preferences->merchant_id =  config('services.cardconnect.mid');
        $user->preferences->merchant_username = 'testing';
        $user->preferences->merchant_password = 'testing123';
        $user->preferences->save();

        return;
    }
}
