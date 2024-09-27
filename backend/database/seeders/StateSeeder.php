<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timezones = config('global.timezones');
        foreach ($timezones as $timezone=>$data)
        {
            foreach ($data as $data=>$state)
            {
                $state['timezone'] = $timezone;
                State::factory()->state($state)->create();
            }
        }
    }
}
