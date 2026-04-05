<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // 🕵️ The loop happens because 'auth' calls 'viaRequest' 
        // which calls 'JWTAuth', which might call 'auth' again.
        $this->app['auth']->viaRequest('api', function ($request) {
            // Check for the token manually first to prevent recursion
            $token = $request->bearerToken();
            
            if ($token) {
                try {
                    // Use the Facade directly
                    return JWTAuth::setToken($token)->authenticate();
                } catch (Exception $e) {
                    return null;
                }
            }
            return null;
        });
    }
}
