<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CandidateProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = CandidateProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json([
                'status' => false,
                'message' => 'Candidate profile not found.',
            ], 404);
        }

        $currentResume = null;

        if (Schema::hasTable('candidate_resumes')) {
            $currentResume = CandidateResume::where('candidate_profile_id', $profile->id)
                ->where('is_current', 1)
                ->latest('id')
                ->first();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ],
                'profile' => $profile,
                'current_resume' => $currentResume ? [
                    'id' => $currentResume->id,
                    'file_name' => $currentResume->file_name,
                    'file_mime_type' => $currentResume->file_mime_type,
                    'file_size_bytes' => $currentResume->file_size_bytes,
                    'version_no' => $currentResume->version_no,
                    'uploaded_at' => optional($currentResume->uploaded_at)?->toISOString(),
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        Log::info('candidate.profile.update.debug', [
            'user_id' => $user?->id,
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'raw' => $request->all(),
        ]);
        

        $profile = CandidateProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json([
                'status' => false,
                'message' => 'Candidate profile not found.',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:150'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],

            'headline' => ['nullable', 'string', 'max:191'],
            'summary' => ['nullable', 'string'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'profile_visibility' => ['nullable', Rule::in(['public', 'private', 'employers_only'])],
            'total_experience_years' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'current_salary' => ['nullable', 'numeric', 'min:0'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'job_type_preference' => ['nullable', Rule::in(['full_time', 'part_time', 'internship', 'any'])],
            'preferred_work_mode' => ['nullable', Rule::in(['remote', 'on_site', 'hybrid', 'any'])],
        ]);

        $userColumns = Schema::getColumnListing('users');
        $profileColumns = Schema::getColumnListing('candidate_profiles');

        $candidateUserFields = [
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'phone',
        ];

        $candidateProfileFields = [
            'headline',
            'summary',
            'location_city',
            'location_state',
            'location_country',
            'profile_visibility',
            'total_experience_years',
            'current_salary',
            'expected_salary',
            'job_type_preference',
            'preferred_work_mode',
        ];

        $userData = [];
        foreach ($candidateUserFields as $field) {
            if (array_key_exists($field, $validated) && in_array($field, $userColumns, true)) {
                $userData[$field] = $validated[$field];
            }
        }

        $profileData = [];
        foreach ($candidateProfileFields as $field) {
            if (array_key_exists($field, $validated) && in_array($field, $profileColumns, true)) {
                $profileData[$field] = $validated[$field];
            }
        }

        if (! empty($userData)) {
            $user->forceFill($userData)->save();
        }

        if (! empty($profileData)) {
            $profile->forceFill($profileData)->save();
        }

        if (in_array('profile_completion_percentage', $profileColumns, true)) {
            $profile->forceFill([
                'profile_completion_percentage' => $this->calculateCompletion($user, $profile),
            ])->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Candidate profile updated successfully.',
            'data' => [
                'user' => $user->fresh(),
                'profile' => $profile->fresh(),
            ],
        ]);
    }

    private function calculateCompletion($user, CandidateProfile $profile): float
    {
        $checks = [
            filled($user->first_name),
            filled($user->email),
            filled($user->phone),
        ];

        $profileColumns = Schema::getColumnListing('candidate_profiles');

        $optionalChecks = [
            'headline' => filled($profile->headline ?? null),
            'summary' => filled($profile->summary ?? null),
            'location_city' => filled($profile->location_city ?? null),
            'location_state' => filled($profile->location_state ?? null),
            'location_country' => filled($profile->location_country ?? null),
            'total_experience_years' => filled($profile->total_experience_years ?? null),
        ];

        foreach ($optionalChecks as $column => $result) {
            if (in_array($column, $profileColumns, true)) {
                $checks[] = $result;
            }
        }

        if (Schema::hasTable('candidate_resumes')) {
            $checks[] = CandidateResume::where('candidate_profile_id', $profile->id)
                ->where('is_current', 1)
                ->exists();
        }

        $filledCount = count(array_filter($checks));
        return round(($filledCount / max(count($checks), 1)) * 100, 2);
    }
}