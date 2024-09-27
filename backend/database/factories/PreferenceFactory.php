<?php

namespace Database\Factories;

use App\Models\Preference;
use Illuminate\Database\Eloquent\Factories\Factory;


class PreferenceFactory extends Factory
{
    protected $model = Preference::class;

    public function definition()
    {
        return [
            'owner_id' => \App\Models\User::factory(),
            'version' => config('app.version'),
            'organization_id' => \App\Models\Organization::factory()
        ];
    }
} 