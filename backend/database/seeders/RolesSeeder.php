<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Exceptions\RoleAlreadyExists;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $perms = new PermissionsSeeder();
        $perms->run();

        try
        {
            Role::create([
                'name' => 'owner'
            ])->syncPermissions($perms->permissions);
        }
        catch (RoleAlreadyExists $e)
        {
            Role::where('name', 'owner')->first()->syncPermissions($perms->permissions);
        }

        try
        {
            Role::create([
                'name' => 'manager'
            ])->syncPermissions($perms->permissions);
        }
        catch (RoleAlreadyExists $e)
        {
            Role::where('name', 'manager')->first()->syncPermissions($perms->permissions);
        }
        
        try
        {
            Role::create([
                'name' => 'employee'
            ])->syncPermissions([
                'scan', 
                'verify',
                'manifest.index',
                'account.profile',
                'account.password'
            ]);
        }
        catch (RoleAlreadyExists $e)
        {
        }

        try
        {
            Role::create([
                'name' => 'admin'
            ])->syncPermissions($perms->adminPerms);
        }
        catch (RoleAlreadyExists $e)
        {
            Role::where('name', 'admin')->first()->syncPermissions($perms->adminPerms);
        }
    }
}
