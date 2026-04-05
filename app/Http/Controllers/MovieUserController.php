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
     * ADD USER: Validates all 3 fields + Distributed Foreign Key Check
     */
    public function add(Request $request)
    {
        // 1. LOCAL VALIDATION (The "Double Lock" Part 1)
        // If movie_id is missing from your Postman JSON, it will now show in the error list.
        $rules = [
            'username' => 'required|max:20|unique:mov_users,username',
            'password' => 'required|max:20',
            'movie_id' => 'required|numeric|min:1', 
        ];

        $this->validate($request, $rules);

        // 2. REMOTE VALIDATION (The "Double Lock" Part 2)
        // Only runs if the local validation above passes.
        $client = new Client();
        $token = $request->header('Authorization');

        try {
            // We call Site 2 to check if the movie exists
            $client->get("http://localhost:8001/movie/" . $request->movie_id, [
                'headers' => ['Authorization' => $token]
            ]);
        } catch (\Exception $e) {
            // This mirrors the "Existing instance" error from your guide
            return response()->json([
                'error' => 'Does not exist any instance of movie with the given id', 
                'site' => 2
            ], 404);
        }

        // 3. SUCCESS: Save to Site 1
        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = MovUser::create($data);
        return response()->json($user, 201);
    }

    /**
     * UPDATE USER: Validates movie_id exists before saving changes
     */
    public function update(Request $request, $id)
    {
        $user = MovUser::findOrFail($id);

        $rules = [
            'username' => 'max:20|unique:mov_users,username,' . $id,
            'password' => 'max:20',
            'movie_id' => 'required|numeric|min:1', 
        ];

        $this->validate($request, $rules);

        $client = new Client();
        $token = $request->header('Authorization');

        try {
            $client->get("http://localhost:8001/movie/" . $request->movie_id, [
                'headers' => ['Authorization' => $token]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed: movie_id not found on Site 2'], 404);
        }
        
        $user->fill($request->all());

        // "At least one value must change" check
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
     * INDEX: List all users and perform the "Virtual Join" with Site 2
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
                $response = $client->get("http://localhost:8001/movie/" . $user->movie_id, [
                    'headers' => ['Authorization' => $token]
                ]);
                $movie = json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                $movie = ['error' => 'Movie details unavailable'];
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

    public function show($id)
    {
        $user = MovUser::findOrFail($id);
        return response()->json($user, 200);
    }

    public function delete($id)
    {
        $user = MovUser::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }
}