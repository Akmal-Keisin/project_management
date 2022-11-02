<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function authRegister(Request $request)
    {
        try {
            // Validation
            $validate = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'email' => [
                    'required',
                    Rule::unique('users')
                ],
                'password' => 'required',
                'confirm_password' => 'required|same:password'
            ]);

            // Validation failed
            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $userData = [];
            $userData['name'] = $request->name;
            $userData['email'] = $request->email;
            $userData['password'] = Hash::make($request->password);
            $user = User::create($userData);

            return Response::success($user, 'Registration Success');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function authLogin(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'password' => 'required'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            if (Auth::attempt($validate->validated())) {
                $token = $request->user()->createToken('auth_token')->plainTextToken;
                $data = Auth::user();
                $data['token'] = $token;
                return Response::success($data, 'Login Success');
            }
            return Response::failed(null, 'Invalid email or password');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function authLogout(Request $request) 
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return Response::success(null, 'Logout Success');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }
}
