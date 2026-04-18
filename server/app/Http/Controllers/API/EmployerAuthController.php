<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmployerAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'company_name' => ['required', 'string', 'max:191'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'company_description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);

        $employerRole = Role::where('slug', 'employer')->firstOrFail();

        $result = DB::transaction(function () use ($validated, $employerRole) {
            $user = User::create([
                'role_id' => $employerRole->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password_hash' => Hash::make($validated['password']),
                'status' => 'active',
                'is_deleted' => 0,
                'email_verified_at' => null,
            ]);

            $company = Company::create([
                'owner_user_id' => $user->id,
                'industry_id' => $validated['industry_id'] ?? null,
                'name' => $validated['company_name'],
                'description' => $validated['company_description'] ?? null,
                'website' => $validated['website'] ?? null,
                'contact_email' => $validated['contact_email'] ?? $validated['email'],
                'contact_phone' => $validated['contact_phone'] ?? ($validated['phone'] ?? null),
                'location_city' => $validated['location_city'] ?? null,
                'location_state' => $validated['location_state'] ?? null,
                'location_country' => $validated['location_country'] ?? null,
                'address_line_1' => $validated['address_line_1'] ?? null,
                'address_line_2' => $validated['address_line_2'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'verification_status' => 'pending',
                'status' => 'active',
            ]);

            EmployerProfile::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'designation' => $validated['designation'] ?? null,
                'department' => $validated['department'] ?? null,
                'is_primary_contact' => 1,
            ]);

            return compact('user', 'company');
        });

        $verificationUrl = URL::temporarySignedRoute(
            'api.auth.employer.verify-email',
            now()->addHours(24),
            [
                'id' => $result['user']->id,
                'hash' => sha1(strtolower($result['user']->email)),
            ]
        );

        $mailSent = false;

        try {
            Mail::raw(
                "Verify your employer account using this link:\n\n{$verificationUrl}",
                function ($message) use ($result) {
                    $message->to($result['user']->email)
                        ->subject('Verify your employer account');
                }
            );
            $mailSent = true;
        } catch (\Throwable $e) {
            // keep registration successful even if mail is not configured yet
        }

        return response()->json([
            'status' => true,
            'message' => 'Employer account created successfully.',
            'user_id' => $result['user']->id,
            'company_id' => $result['company']->id,
            'mail_sent' => $mailSent,
            'verification_url' => config('app.debug') ? $verificationUrl : null,
        ], 201);
    }
}