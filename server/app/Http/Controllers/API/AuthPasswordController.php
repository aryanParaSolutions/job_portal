<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthPasswordController extends Controller
{
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $user = User::where('email', $validated['email'])
            ->where('is_deleted', 0)
            ->first();

        if (! $user) {
            return response()->json([
                'status' => true,
                'message' => 'If the account exists, a password reset link has been sent.',
            ]);
        }

        $token = Password::broker()->createToken($user);

        return response()->json([
            'status' => true,
            'message' => 'Password reset token generated successfully.',
            'reset_token' => config('app.debug') ? $token : null,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password_hash' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'status' => false,
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => __($status),
        ]);
    }
}