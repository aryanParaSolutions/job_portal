<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CandidateAuthController extends Controller
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

            'headline' => ['nullable', 'string', 'max:191'],
            'summary' => ['nullable', 'string'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'profile_visibility' => ['nullable', 'in:public,private,employers_only'],
            'total_experience_years' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'current_salary' => ['nullable', 'numeric', 'min:0'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'job_type_preference' => ['nullable', 'in:full_time,part_time,internship,any'],
            'preferred_work_mode' => ['nullable', 'in:remote,on_site,hybrid,any'],
        ]);

        $candidateRole = Role::where('slug', 'candidate')->firstOrFail();

        $user = DB::transaction(function () use ($validated, $candidateRole) {
            $user = User::create([
                'role_id' => $candidateRole->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password_hash' => Hash::make($validated['password']),
                'status' => 'active',
                'is_deleted' => 0,
            ]);

            CandidateProfile::create([
                'user_id' => $user->id,
                'headline' => $validated['headline'] ?? null,
                'summary' => $validated['summary'] ?? null,
                'location_city' => $validated['location_city'] ?? null,
                'location_state' => $validated['location_state'] ?? null,
                'location_country' => $validated['location_country'] ?? null,
                'profile_visibility' => $validated['profile_visibility'] ?? 'employers_only',
                'profile_completion_percentage' => 0,
                'total_experience_years' => $validated['total_experience_years'] ?? null,
                'current_salary' => $validated['current_salary'] ?? null,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'job_type_preference' => $validated['job_type_preference'] ?? 'any',
                'preferred_work_mode' => $validated['preferred_work_mode'] ?? 'any',
            ]);

            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => 'Candidate account created successfully.',
            'user_id' => $user->id,
        ], 201);
    }
}