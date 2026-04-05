<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// --- PUBLIC ROUTES ---
$router->post('/MovUser/login', 'AuthController@login');
$router->post('/MovUser/register', 'AuthController@register');
 
$router->get('/test', function () {
    return 'Route working';
});

// --- PROTECTED ROUTES ---
$router->group(['middleware' => 'auth'], function () use ($router) {

    $router->get('/profile', function () {
        return response()->json(auth()->user());
    });

    // MOVIE USERS (Site 1)
    // These now use Guzzle to talk to Site 2 (Port 8001)
    $router->get('/MovUser', 'MovieUserController@index');
    $router->post('/MovUser', 'MovieUserController@add'); 
    $router->get('/MovUser/{id}', 'MovieUserController@show');
    $router->put('/MovUser/{id}', 'MovieUserController@update');
    $router->delete('/MovUser/{id}', 'MovieUserController@delete');
});