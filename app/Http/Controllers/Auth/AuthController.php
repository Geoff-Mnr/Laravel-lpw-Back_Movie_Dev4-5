<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use illuminate\Support\Facades\Storage;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                
                if ($user->is_active == 0) {
                    return $this->handleError('Your account is banned', 403);
                }

                $token = $user->createToken('LaravelSanctumAuth');
                $plainToken = $token->plainTextToken;
    
                $expirationHours= 12;
                $expiresAt = now()->addHours($expirationHours)->toDateTimeString();
    
                return $this->handleResponseNoPagination('Login successful', [
                    'user' => [
                        'username' => $user->username,
                        'email' => $user->email,
                        'role_name' => $user->role->name ?? 'User'

                    ],  
                    'access_token'=> $plainToken, 
                    'token_type' => 'Bearer', 
                    'expires_at' => $expiresAt
                ], 200);
            } else {
                return $this->handleError('Invalid email or password', 401);
            }
        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), 400);
        }
    }

    public function register(Request $request)
    {
        try{
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',

        ]);

        $input = $request->all();

        $input['role_id'] = 1; 
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $success['token'] = $user->createToken('LaravelSanctumAuth')->plainTextToken;
        $success['user'] = $user;

        return $this->handleResponseNoPagination('User registered successfully', $success, 201);
        } catch (Exception $e) {
            return $this->handleError($e->getMessage(), 500);
        }
    }
    

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return $this->handleResponseNoPagination('Logged out', null, 200);
    }
}