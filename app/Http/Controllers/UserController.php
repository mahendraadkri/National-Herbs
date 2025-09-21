<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index(Request $request)
    {
        $users = User::latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data'    => $users,
        ]);
    }

    /**
     * Create a new user.
     */
    //  public function register(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name'     => ['required', 'string', 'max:255'],
    //         'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
    //         'password' => ['required', 'string', 'min:8', 'confirmed'],
    //     ]);

    //     $user = User::create([
    //         'name'     => $validated['name'],
    //         'email'    => $validated['email'],
    //         'password' => Hash::make($validated['password']),
    //     ]);

    //     // Create token (add abilities if you want, e.g., ['*'])
    //     $token = $user->createToken('api')->plainTextToken;

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Registered successfully.',
    //         'data'    => [
    //             'user'       => $user,
    //             'token_type' => 'Bearer',
    //             'token'      => $token,
    //         ],
    //     ], 201);
    // }

    /**
     * Show a single user.
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data'    => $user,
        ]);
    }

    /**
     * Update an existing user.
     */
    // public function update(Request $request, User $user)
    // {
    //     $validated = $request->validate([
    //         'name'     => ['sometimes', 'required', 'string', 'max:255'],
    //         'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
    //         'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
    //     ]);

    //     if (isset($validated['password'])) {
    //         $validated['password'] = Hash::make($validated['password']);
    //     }

    //     $user->update($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User updated successfully.',
    //         'data'    => $user,
    //     ]);
    // }


    /**
     * Login existing user and return token.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data'    => [
                'user'       => $user,
                'token_type' => 'Bearer',
                'token'      => $token,
            ],
        ]);
    }

    /**
     * Invalidate current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile fetched.',
            'data'    => $request->user(),
        ]);
    }

    /**
     * Delete a user.
     */
    // public function destroy(User $user)
    // {
    //     $user->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User deleted successfully.',
    //     ]);
    // }
}
