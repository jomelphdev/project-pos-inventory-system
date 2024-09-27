<?php

namespace App\Policies;

use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PosOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('pos.orders') && $user->organization_id;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\PosOrder  $posOrder
     * @return mixed
     */
    public function view(User $user, PosOrder $posOrder)
    {
        return $user->organization_id == $posOrder->organization_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('pos.index');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\PosOrder  $posOrder
     * @return mixed
     */
    public function update(User $user, PosOrder $posOrder)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\PosOrder  $posOrder
     * @return mixed
     */
    public function delete(User $user, PosOrder $posOrder)
    {
        //
    }
}
