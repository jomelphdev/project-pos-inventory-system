<?php

namespace Database\Factories;

use App\Models\ConsignorInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsignorInvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConsignorInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'consignor_id' => getRandomRow(new \App\Models\Consignor, 'preference_id'),
            'amount_paid' => $this->faker->numberBetween(1, 10000),
            'amount_collected' => $this->faker->numberBetween(1, 10000)
        ];
    }
}
