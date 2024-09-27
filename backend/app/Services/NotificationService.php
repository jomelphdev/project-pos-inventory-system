<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class NotificationService 
{
    public function createNotification($notificationData)
    {
        $userIds = User::select('id')->whereHas('organization', function (Builder $q) {
            $q->whereDate('trial_ends_at', '>', now())->orWhereHas('subscription', function (Builder $q) {
                $q->whereNull('ends_at');
            });
        })->role($notificationData['recipients'])->get()->pluck('id');

        foreach ($userIds as $id)
        {
            try
            {
                $notification = new Notification([
                    'user_id' => $id,
                    'header' => $notificationData['header'],
                    'body' => $notificationData['body'],
                    'footer' => $notificationData['footer']
                ]);
                $notification->save();
            }
            catch (Exception $e)
            {
                Log::warning('USER: ' . $id . ' did not receive the notification. ERROR: Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
                continue;
            }
        }
    }
}

?>