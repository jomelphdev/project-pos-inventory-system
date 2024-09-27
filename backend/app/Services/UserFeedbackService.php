<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFeedback;

class UserFeedbackService
{
    public static function createManyForUser(User $user, array $allFeedback)
    {
        foreach ($allFeedback as $feedback)
        {
            $feedback['organization_id'] = $user->organization_id;
            $feedback['user_id'] = $user->id;
            
            UserFeedback::create($feedback);
        }
    }
}