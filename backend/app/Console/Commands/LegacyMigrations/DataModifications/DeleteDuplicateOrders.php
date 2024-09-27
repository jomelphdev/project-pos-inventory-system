<?php

namespace App\Console\Commands\LegacyMigrations\DataModifications;

use App\CustomClass\LegacyConnection;
use App\Models\Organization;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteDuplicateOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-modification:delete-dupe-orders {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all duplicate orders from Organization';

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
        $mongoId = $this->argument('user');

        $adminUser = User::where('mongo_id', $mongoId)->first();
        $orgId = $adminUser->organization_id;

        $baseQuery = PosOrder::select('organization_id', 'mongo_id')
            ->where('organization_id', $orgId);

        $ids = clone $baseQuery;
        $ids = $ids->groupBy('organization_id', 'mongo_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->pluck('mongo_id');
        
        foreach ($ids as $id)
        {
            $orders = clone $baseQuery;
            $orders = $orders->select('id')->where('mongo_id', $id);
            $orderToDelete = $orders->get()->last();
            $orderToDelete->posOrderItems()->forceDelete();
            $orderToDelete->forceDelete();

            echo('Order Dupe Deleted ID: ' . $id . "\n");
        }

        echo('ALL DUPES DELETED!');
    }
}
