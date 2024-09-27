<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function createNotification(Request $request, NotificationService $notificationService)
    {
        $notificationData = $request->validate([
            'recipients' => 'array|min:1|required',
            'header' => 'string|nullable',
            'body' => 'string|required',
            'footer' => 'string|nullable'
        ]);

        try
        {
            $notificationService->createNotification($notificationData);
        }
        catch (Exception $e)
        {
            return response()->error("Something went wrong while trying to create notification.");
        }

        return response()->success();
    }
}
