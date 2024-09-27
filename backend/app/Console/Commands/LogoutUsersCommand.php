<?php

namespace App\Console\Commands;

use App\Jobs\LogoutUsers;
use Illuminate\Console\Command;

class LogoutUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:logout {users? : list of user IDs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a notification to logout to all users whose ID is passed.';

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
        LogoutUsers::dispatch($this->argument('users'));
    }
}
