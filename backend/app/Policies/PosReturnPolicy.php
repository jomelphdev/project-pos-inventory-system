<?php

namespace App\Policies;

use App\Models\PosReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PosReturnPolicy
{
    use HandlesAuthorization;

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
}
