<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MigrateEmployeesForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:employees {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates employees that dont exist yet.';

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
        $userId = $this->argument('user');
        $adminUser = User::where('mongo_id', $userId)->first();
        $conn = new LegacyConnection($userId);
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        
        $employees = $conn->users()->find(['employer' => $userId], $options);
        foreach ($employees as $user)
        {
            $employeeId = $user['_id']->__toString();

            if (User::where('mongo_id', $employeeId)->first())
            {
                continue;
            }

            var_dump('Trying to create ' . $employeeId);

            $user['organization_id'] = $adminUser->organization_id;
            $user['permissions'] = array_diff(
                Permission::getPermissionsArray(), [
                'scan', 
                'verify',
                'manifest.index',
                'account.profile',
                'account.password'
            ]);
            $this->createUser($user, true);

            var_dump($employeeId . 'was created!');
        }
    }

    private function createUser($user, $isEmployee=false)
    {
        $userName = explode(" ", $user['name']);
        $newUser = [
            'mongo_id' => $user['_id']->__toString(),
            'first_name' => $userName[0],
            'last_name' => isset($userName[1]) ? $userName[1] : null,
            'email' => $user['email'],
            'username' => $user['username'],
            'password' => 'Temp123',
            'employee' => $isEmployee,
            'organization_id' => isset($user['organization_id']) ? $user['organization_id'] : null,
            'permissions' => $isEmployee ? $user['permissions'] : null
        ];

        $req = Http::post(config('app.url') . '/api/users/create', $newUser);
        var_dump($req->json());
        return $newUser;
    }
}
