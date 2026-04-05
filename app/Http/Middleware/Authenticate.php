<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use App\Models\MovUser; 

class Authenticate extends BaseMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $header = $request->header('Authorization');

        if (!$header || strpos($header, 'Bearer ') !== 0) {
            return response()->json(['status' => 'Unauthorized', 'message' => 'Token missing.'], 401);
        }

        try {
            // Manually get payload to avoid onceUsingId crash
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = $payload->get('sub');

            $user = MovUser::find($userId);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Plug the user into the request
            app('auth')->setUser($user);

        } catch (Exception $e) {
            return response()->json(['status' => 'Error', 'message' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
