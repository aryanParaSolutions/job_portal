<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmployerEmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired verification link.',
            ], 403);
        }

        $user = User::with('role')->findOrFail($id);

        if ($user->role?->slug !== 'employer') {
            return response()->json([
                'status' => false,
                'message' => 'Invalid employer verification request.',
            ], 403);
        }

        if (! hash_equals((string) $hash, sha1(strtolower($user->email)))) {
            return response()->json([
                'status' => false,
                'message' => 'Email verification hash mismatch.',
            ], 403);
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Employer email verified successfully.',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('role');

        if ($user->role?->slug !== 'employer') {
            return response()->json([
                'status' => false,
                'message' => 'Only employer accounts can request employer verification.',
            ], 403);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Email is already verified.',
            ], 409);
        }

        $verificationUrl = URL::temporarySignedRoute(
            'api.auth.employer.verify-email',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1(strtolower($user->email)),
            ]
        );

        $mailSent = false;

        try {
            Mail::raw(
                "Verify your employer account using this link:\n\n{$verificationUrl}",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Verify your employer account');
                }
            );
            $mailSent = true;
        } catch (\Throwable $e) {
            //
        }

        return response()->json([
            'status' => true,
            'message' => 'Verification email processed.',
            'mail_sent' => $mailSent,
            'verification_url' => config('app.debug') ? $verificationUrl : null,
        ]);
    }
}