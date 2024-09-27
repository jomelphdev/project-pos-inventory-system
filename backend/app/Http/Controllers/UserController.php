<?php

namespace App\Http\Controllers;

use App\Exceptions\RetailRightException;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function authenticate(Request $request, User $user)
    {
        try
        {
            $user = UserService::authenticate($request->input('username'), $request->input('password'));
        }
        catch (RetailRightException $e)
        {
            return response()->error($e->getMessage());
        }
        catch (Exception $e)
        {
            Log::error($e);
            return response()->error('Something went wrong while trying to authenticate user.');
        }
        
        return response()->success(['user' => new UserResource($user)]);
    }

    public function store(Request $request)
    {
        $newUser = $request->validate([
            'organization_id' => 'integer|nullable',
            'first_name' => 'string|required',
            'last_name' => 'string|nullable',
            'email' => 'string|required',
            'username' => 'string|required',
            'password' => 'string|min:8|required',
            'role' => 'string|nullable',
            'feedback' => 'array|nullable',
            'permissions' => 'array|nullable'
        ]);

        try
        {
            $user = UserService::createUser($newUser);
        }
        catch (Exception $e)
        {
            return response()->error("Something went wrong while trying to create User.");
        }

        return response()->success(['user' => new UserResource($user)]);
    }

    public function update(Request $request, $userId)
    {
        $request->merge(['id' => $userId]);
        $update = $request->validate([
            'id' => 'required|int|exists:users,id',
            'update' => 'required|array',
            'update.first_name' => 'nullable|string',
            'update.last_name' => 'nullable|string',
            'update.email' => 'nullable|string',
            'update.email_verified_at' => 'nullable|boolean',
            'update.username' => 'nullable|string',
            'update.password' => 'nullable|string|min:8'
        ])['update'];

        try
        {
            $user = User::withTrashed()->findOrFail($userId);
        }
        catch (ModelNotFoundException $e)
        {
            return response()->error('User does not exist.');
        }

        $this->authorize('update', $user);

        try
        {
            DB::beginTransaction();

            if (isset($update['password'])) 
            {
                $update['password'] = Hash::make($update['password']);
            }

            if (isset($update['role']))
            {
                if ($update['role'] == 'manager')
                {
                    $user->permissions()->detach();
                }

                $user->roles()->detach();
                $user->assignRole($update['role']);
            }

            if (isset($update['permissions']))
            {
                $user->syncPermissions($update['permissions']);
            }

            if (isset($update['deleted_at']))
            {
                if ($update['deleted_at'] === false && !is_null($user->deleted_at))
                {
                    $user->restore();
                }
                else if ($update['deleted_at'] != false)
                {
                    $user->delete();
                }

                unset($update['deleted_at']);
            }

            $user->fill($update);

            if ($user->isDirty('email'))
            {
                $user->email_verified_at = null;
                $user->sendEmailVerificationNotification();
            }
            
            $user->save();

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to update user.');
        }

        return response()->success(['user' => $user]);
    }

    public function show($id)
    {
        try
        {
            $user = User::findOrFail($id);
        }
        catch (ModelNotFoundException $e)
        {
            return response()->error('User does not exist.');
        }

        $this->authorize('view', $user);

        return response()->success(['user' => new UserResource($user)]);
    }

    public function verifyPassword(Request $request)
    {
        $userId = $request->user()->id;
        $password = $request->input('password');
        $user = User::find($userId);
        if (Hash::check($password, $user->password)) return response()->success(['isMatch' => true]);
        return response()->success(['isMatch' => false]);
    }

    public function markNotificationRead(Request $request)
    {
        $request->validate(['notification_id' => 'required|exists:notifications,id']);

        $notification = Notification::find($request->notification_id);
        $notification->read = true;
        $notification->save();

        return response()->success();
    }
}


