<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;

class SeedUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:user {username : Username to generate user with} {email : Your email for verification} {--password} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds user with custom username and optionally password';

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
        $username = $this->argument("username");

        if ($user = User::where("username", $username)->first())
        {
            if (!$this->option("force"))
            {
                $user->organization()->forceDelete();
            }
            else
            {
                echo("\nUser " . $username . ' already exists. If you would still like to create user add --force tag.');
                return;
            }
        }

        UserService::createUser([
            "first_name" => "Bruce",
            "last_name" => "Wayne",
            "email" => $this->argument("email"),
            "username" => $username,
            "password" => $this->option("password") ? $this->option("password") : "password"
        ]);

        return;
    }
}
