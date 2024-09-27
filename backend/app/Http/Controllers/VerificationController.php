<?php

namespace App\Http\Controllers;

use App\Events\LateReply;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class VerificationController extends Controller
{
    public function verify($id)
    {
        try
        {
            $user = User::findOrFail($id);
            $date = date("Y-m-d g:i:s");

            DB::beginTransaction();

            $user->email_verified_at = $date;
            $user->save();

            DB::commit();

            event(new LateReply([
                'success' => true,
                'user_id' => $id,
                'message' => 'Email has been verified!',
                'response_type' => 'email-verification'
            ]));

            return Redirect::to('https://retailright.app');
        } catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to verify this e-mail.');
        }
    }

    public function resend(User $user, $id)
    {
        try
        {
            $user = User::findOrFail($id);

            if ($user->hasVerifiedEmail())
            {
                event(new LateReply([
                    'success' => true,
                    'user_id' => $id,
                    'message' => 'Email has already been verified!',
                    'response_type' => 'email-verification'
                ]));
            }

            $user->sendEmailVerificationNotification();
            return response()->success();
        } catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to send email verification.');
        }
    }
}
