<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Permission;
use App\Models\Preference;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\ObjectId;

class MigrateMongoUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:user {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates User data from MongoDB to this DB';

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
        $conn = new LegacyConnection($userId);
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $legacyUser = $conn->users()->findOne(['_id' => new ObjectId($userId)], $options);
        $legacyPreferences = $conn->preferences()->findOne(['_id' => new ObjectId($legacyUser['preferenceId'])], $options);

        if ($this->doesUserExist($userId))
        {
            dd('User already exists.');
            return;
        }


        $user = $this->createUser($legacyUser);
        $auth = Http::post(config('app.url') . '/api/users/authenticate', $user);
        $authBody = $auth->json();
        $newUser = $authBody['data']['user'];
        // $token = $newUser['token'];
        // $orgId = $newUser['organization_id'];
        $preferenceId = $newUser['organization']['preferences']['id'];

        $employees = $conn->users()->find(['employer' => $legacyUser['_id']->__toString()], $options);
        foreach ($employees as $user)
        {
            $user['organization_id'] = $newUser['organization_id'];
            $user['permissions'] = array_diff(
                Permission::getPermissionsArray(), [
                'scan', 
                'verify',
                'manifest.index',
                'account.profile',
                'account.password'
            ]);
            $this->createUser($user, true);
        }
        
        $classifications = array_map(function ($c) {
            $c = $this->adjustPreferenceFields($c);
            $c['is_ebt'] = isset($c['isEbt']) ? $c['isEbt'] : false;
            $c['is_taxed'] = isset($c['isTaxed']) ? $c['isTaxed'] : true;
            return $c;
        }, $legacyPreferences['classifications']);

        $conditions = array_map('self::adjustPreferenceFields', $legacyPreferences['conditions']);
        $discounts = array_map('self::adjustPreferenceFields', $legacyPreferences['discounts']);
        $preference = Preference::find($preferenceId);

        try
        {
            DB::beginTransaction();

            $preference->classifications()->createMany($classifications);
            $preference->conditions()->createMany($conditions);
            $preference->discounts()->createMany($discounts);

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            var_dump($e->getMessage());
        }

        dd('User and their preferences created successfully!');
    }

    private function adjustPreferenceFields($item)
    {
        $item['mongo_id'] = $item['_id'];
        $item['discount'] = $item['discount'] * 100;
        $item['deleted_at'] = isset($item['hidden']) && $item['hidden'] ? now()->timestamp : null; 
        unset($item['_id']);
        return $item;
    }

    private function doesUserExist($id) 
    {
        try
        {
            User::where('mongo_id', $id)->firstOrFail();
            return true;
        }
        catch (ModelNotFoundException $e)
        {
            return false;
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

        return $newUser;
    }
}
