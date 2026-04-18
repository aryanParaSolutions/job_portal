<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CandidateResumeController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $profile = CandidateProfile::where('user_id', $request->user()->id)->firstOrFail();

        $resume = CandidateResume::where('candidate_profile_id', $profile->id)
            ->where('is_current', 1)
            ->latest('id')
            ->first();

        return response()->json([
            'status' => true,
            'data' => $resume ? [
                'id' => $resume->id,
                'file_name' => $resume->file_name,
                'file_mime_type' => $resume->file_mime_type,
                'file_size_bytes' => $resume->file_size_bytes,
                'version_no' => $resume->version_no,
                'uploaded_at' => optional($resume->uploaded_at)?->toISOString(),
            ] : null,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $profile = CandidateProfile::where('user_id', $request->user()->id)->firstOrFail();
        $file = $validated['resume'];

        $nextVersion = ((int) CandidateResume::where('candidate_profile_id', $profile->id)->max('version_no')) + 1;

        $storedName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'candidate-resumes/' . $profile->id,
            $storedName,
            'local'
        );

        DB::transaction(function () use ($profile, $file, $storedPath, $nextVersion) {
            CandidateResume::where('candidate_profile_id', $profile->id)
                ->where('is_current', 1)
                ->update(['is_current' => 0]);

            CandidateResume::create([
                'candidate_profile_id' => $profile->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'file_size_bytes' => $file->getSize(),
                'storage_disk' => 'local',
                'is_current' => 1,
                'version_no' => $nextVersion,
                'uploaded_at' => now(),
            ]);

            $profile->profile_completion_percentage = $this->calculateCompletion($profile);
            $profile->save();
        });

        return response()->json([
            'status' => true,
            'message' => 'Resume uploaded successfully.',
        ], 201);
    }

    private function calculateCompletion(CandidateProfile $profile): float
    {
        $user = $profile->user;

        $checks = [
            filled($user->first_name),
            filled($user->email),
            filled($user->phone),
            filled($profile->headline),
            filled($profile->summary),
            filled($profile->location_city),
            filled($profile->location_state),
            filled($profile->location_country),
            filled($profile->total_experience_years),
            CandidateResume::where('candidate_profile_id', $profile->id)->where('is_current', 1)->exists(),
        ];

        $filledCount = count(array_filter($checks));
        return round(($filledCount / count($checks)) * 100, 2);
    }
}