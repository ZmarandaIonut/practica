<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use DB;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

/**
 *
 */
class UserController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $error = false;

            /** @var User $user */
            $user = User::where('email', $request->get('email'))->first();
            if (!$user) {
                $error = true;
            } else {
                if (!Hash::check($request->get('password'), $user->password)) {
                    $error = true;
                }
            }

            if ($error) {
                return $this->sendError('Bad credentials!');
            }

            $token = $user->createToken('Practica');

            Auth::login($user);

            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     *
     */
    public function register(Request $request): JsonResponse
    {
 
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $user = new User();
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->password = Hash::make($request->get('password'));
            $user->save();

            $token = $user->createToken('Practica');
            
            event(new Registered($user));


            return $this->sendResponse([
                'token' => $token->plainTextToken,
                'user' => $user->toArray()
            ]);
         
    }

    public function forgotPassword(Request $request){
        try{
            $userEmail = $request->get("email");
            $user = User::where("email", $userEmail)->first();
            if(!$user){
              return $this->sendError("This email doesn't belong to any account.", [], Response::HTTP_NOT_FOUND);
            }
            DB::table("password_resets")->insert([
             'email' => $userEmail,
             'token' => Str::random(40),
             'created_at' => Carbon::now()
            ]);
            $data = DB::table("password_resets")->where("email", $userEmail)->first();
            return $this->sendResponse($data);
        }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function resetPassword($token, Request $request){
        try{
            $user = DB::table("password_resets")->where("token", $token)->first();
            if(!$user){
               return $this->sendError("This token dosen't exist", [], Response::HTTP_NOT_FOUND);
            }
            $userAccount = User::where("email", $user->email)->first();
     
            $validator = Validator::make($request->all(), [
             'new_password' => 'required|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $newPassword = $request->get("new_password");
     
            $userAccount->password = Hash::make($newPassword);
     
            $userAccount->save();

            return $this->sendResponse($userAccount->toArray());
        }
        catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
