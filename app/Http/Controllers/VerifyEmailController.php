<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;

class VerifyEmailController extends ApiController
{
    public function sendVerifyEmail(Request $request){
        try{
        $user = User::find($request->route("id"));

        if($user->hasVerifiedEmail()){
            return $this->sendError("Email is already verified!");
        }
        if($user->markEmailAsVerified()){
            event(new Verified($user));
        }

        return $this->sendResponse($user);
       }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function resendEmailVerification(Request $request){
        try{
            $request->user()->sendEmailVerificationNotification();
            return $this->sendResponse([]);
        }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
