<?php

namespace App\Console\Commands;

use Database\Seeders\PosOrderSeeder as OrderSeeder;
use Illuminate\Console\Command;

class PosOrderSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:pos-order {--stores=3} {--orders-per-store=3} {--items-per-order=3} {--one-user=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds DB with all data needed to make a POS Order and generate sales reports. More: https://github.com/RetailRight/retail-right-api-v3/issues/20';

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
        $seeder = new OrderSeeder;
        $seeder->callWith(OrderSeeder::class, [$this->option('stores'), $this->option('orders-per-store'), $this->option('items-per-order'), $this->option('one-user')]);

        return 0;
    }
}
