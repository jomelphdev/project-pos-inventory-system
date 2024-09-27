<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;


class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition()
    {
        return [
            'name' => 'Arizona',
            'abbreviation' => 'AZ',
            'timezone' => 'America/Phoenix'
        ];
    }
}
