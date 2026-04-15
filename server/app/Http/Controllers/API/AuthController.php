<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register new user
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'full_name' => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'address'  => 'nullable|string|max:255',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::create([
                'full_name' => $data['full_name'],
                'email'    => $data['email'],
                'address'  => $data['address'] ?? null,
                'password' => bcrypt($data['password']),
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user'  => $user,
                'token' => $token
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    // Login user
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.']
                ]);
            }

            // Create token
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'user'   => $user,
                'token'  => $token
            ], 200);
        } catch (ValidationException $e) {
            // Return validation errors as JSON
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            // Return any other exception as JSON
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Fetch logged-in user profile
    public function profile(Request $request)
    {
        try {
            return response()->json($request->user());
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::user(); // Authenticated user via Sanctum
            // dd($user);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|min:6|confirmed',
                'address' => 'sometimes|string|max:255',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update fields dynamically
            if ($request->has('full_name')) {
                $user->full_name = $request->full_name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('password')) {
                $user->password = bcrypt($request->password);
            }

            if ($request->has('address')) {
                $user->address = $request->address;
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Logout user (delete current token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
