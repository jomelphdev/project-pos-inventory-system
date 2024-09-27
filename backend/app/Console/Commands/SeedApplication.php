<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:app {--with-test-user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds all data to run the app, as well as all required data to run E2E tests.';

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
        Artisan::call("db:seed", [
            "--class" => "StateSeeder"
        ]);
        Artisan::call("db:seed", [
            "--class" => "RolesSeeder"
        ]);
        
        if ($this->option('with-test-user'))
        {
            Artisan::call("e2e:seed-test-user");
        }
        
        return;
    }
}
