<?php

namespace App\Http\Controllers;

use App\Models\MovUser;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class MovieUserController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * GET ALL USERS (Index)
     * Includes distributed data from Site 2
     */
    public function index()
    {
        $users = MovUser::all();
        $client = new Client();
        $token = $this->request->header('Authorization');
        $result = [];

        foreach ($users as $user) {
            $movie = null;
            try {
                // Call Site 2 to get movie details
                $response = $client->get("https://site2-microservice.onrender.com/movie/" . $user->movie_id, [
                    'headers' => ['Authorization' => $token]
                ]);
                $movie = json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                $movie = ['error' => 'Movie details unavailable (Site 2 connection error)'];
            }

            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                'movie_id' => $user->movie_id,
                'movie_details' => $movie
            ];
        }

        return response()->json($result, 200);
    }

    /**
     * SHOW SINGLE USER
     * Now returns a clean "Deleted" message if the ID is missing
     */
    public function show($id)
    {
        $user = MovUser::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found or has been deleted'], 404);
        }

        return response()->json($user, 200);
    }

    /**
     * ADD USER
     * Validates Site 2 ID before saving to Aiven
     */
    public function add(Request $request)
    {
        $rules = [
            'username' => 'required|max:20|unique:mov_users,username',
            'password' => 'required|max:20',
            'movie_id' => 'required|numeric|min:1', 
        ];

        $this->validate($request, $rules);

        $client = new Client();
        $token = $request->header('Authorization');

        try {
            // Foreign Key Check on Site 2
            $client->get("https://site2-microservice.onrender.com/movie/" . $request->movie_id, [
                'headers' => ['Authorization' => $token]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Does not exist any instance of movie with the given id', 
                'site' => 2
            ], 404);
        }

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = MovUser::create($data);
        return response()->json($user, 201);
    }

    /**
     * UPDATE USER
     */
    public function update(Request $request, $id)
    {
        $user = MovUser::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $rules = [
            'username' => 'max:20|unique:mov_users,username,' . $id,
            'password' => 'max:20',
            'movie_id' => 'required|numeric|min:1', 
        ];

        $this->validate($request, $rules);

        $client = new Client();
        $token = $request->header('Authorization');

        try {
            $client->get("https://site2-microservice.onrender.com/movie/" . $request->movie_id, [
                'headers' => ['Authorization' => $token]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed: movie_id not found on Site 2'], 404);
        }
        
        $user->fill($request->all());

        if ($user->isClean()) {
            return response()->json(['error' => 'At least one value must change'], 422);
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        return response()->json($user, 200);
    }

    /**
     * DELETE USER
     * Returns a confirmation even if you hit it with GET later
     */
    public function delete($id)
    {
        $user = MovUser::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found or already deleted'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}