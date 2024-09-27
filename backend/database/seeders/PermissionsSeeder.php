<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;

class PermissionsSeeder extends Seeder
{
    public $employeePrefixes = [
        'items',
        'pos',
        'reports',
        'import'
    ];

    public $permissions = [
        'scan',
        'verify',
        'import',
        'items.index',
        'items.create',
        'items.edit',
        'items.test',
        'manifest.index',
        'reports.daily-sales',
        'reports.sales-report',
        'reports.item-sales',
        'reports.inventory',
        'reports.drawers',
        'reports.consignment',
        'reports.consignment-invoices',
        'reports.quickbooks',
        'reports.gift-card',
        'pos.index',
        'pos.returns',
        'pos.orders',
        'pos.orders.details',
        'pos.gift-cards',
        'pos.gift-cards.edit',
        'preferences.classifications',
        'preferences.conditions',
        'preferences.discounts',
        'preferences.stores',
        'preferences.employees',
        'preferences.consignors',
        'preferences.site',
        'preferences.checkoutStations',
        'preferences.pos',
        'preferences.billing',
        'preferences.quickbooks',
        'account.profile',
        'account.password'
    ];

    public $adminPerms = [
        'verify',
        'admin.announcements'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission)
        {
            try
            {
                $permission = Permission::create([
                    'name' => $permission
                ]);

                /* 
                If is a prefixed permission - add it to existing employee
                permissions where the employee has existing permissions
                matching the prefix.

                Ex: If employee has items.create and there is a new perm
                items.delete add items.delete to employee because matching
                prefix items.
                */
                if (Role::where('name', 'employee')->first() && strpos($permission->name, '.') !== false)
                {
                    $splitPerm = explode('.', $permission->name);
                    $prefix = $splitPerm[0];

                    if (in_array($prefix, $this->employeePrefixes))
                    {
                        $prefixRelatedPermissions = array_filter($this->permissions, function ($perm) use ($prefix) {
                            if (strpos($perm, $prefix) !== false) return true;
                            return false;
                        });

                        $employees = User::role('employee')->permission($prefixRelatedPermissions)->get();

                        foreach ($employees as $user)
                        {
                            $user->givePermissionTo($permission->name);
                        }
                    }
                }
            }
            catch (PermissionAlreadyExists $e)
            {
                continue;
            }
        }

        foreach ($this->adminPerms as $permission)
        {
            try
            {
                $permission = Permission::create([
                    'name' => $permission
                ]);
            }
            catch (PermissionAlreadyExists $e)
            {
                continue;
            }
        }
    }
}
