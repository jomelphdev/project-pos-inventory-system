<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function view(User $user, User $model)
    {
        return $user->id == $model->id || 
            ($user->hasRole('owner') && 
                $user->organization_id == $model->organization_id);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        return $user->id == $model->id || 
            (
                $user->organization_id == $model->organization_id &&
                (
                    $user->hasRole('owner') ||
                    $user->hasRole('manager') &&
                        $model->hasRole('employee')
                )
            );
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    // public function delete(User $user, User $model)
    // {
    //     //
    // }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    // public function restore(User $user, User $model)
    // {
    //     //
    // }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    // public function forceDelete(User $user, User $model)
    // {
    //     //
    // }
}