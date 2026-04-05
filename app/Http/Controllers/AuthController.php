<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\MovUser;
use Tymon\JWTAuth\Facades\JWTAuth;



class AuthController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|unique:mov_users',
            'password' => 'required|string',
            'movie_id' => 'required|integer'
        ]);

        $user = new MovUser();
        $user->username = $request->input('username');
        $user->password = Hash::make($request->input('password')); 
        $user->movie_id = $request->input('movie_id');
        $user->save();

        return response()->json(['message' => 'User created! Now you can login.', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = MovUser::where('username', $request->input('username'))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (Hash::check($request->input('password'), $user->password)) {
            $token = JWTAuth::fromUser($user); 
            return $this->respondWithToken($token);
        }

        return response()->json(['message' => 'Invalid password'], 401);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 3600 
        ]);
    }
}