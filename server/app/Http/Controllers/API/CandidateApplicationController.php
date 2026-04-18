<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateApplicationController extends Controller
{
    public function apply(Request $request, int $jobId): JsonResponse
    {
        $validated = $request->validate([
            'resume_id' => ['nullable', 'integer', 'exists:candidate_resumes,id'],
            'cover_letter_text' => ['nullable', 'string'],
            'cover_letter_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $job = Job::publiclyVisible()->findOrFail($jobId);

        $profile = CandidateProfile::where('user_id', $user->id)->firstOrFail();

        $alreadyApplied = JobApplication::where('job_id', $job->id)
            ->where('candidate_user_id', $user->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json([
                'status' => false,
                'message' => 'You have already applied to this job.',
            ], 409);
        }

        $resume = null;

        if (! empty($validated['resume_id'])) {
            $resume = CandidateResume::where('id', $validated['resume_id'])
                ->where('candidate_profile_id', $profile->id)
                ->first();

            if (! $resume) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected resume does not belong to the candidate.',
                ], 422);
            }
        } else {
            $resume = CandidateResume::where('candidate_profile_id', $profile->id)
                ->where('is_current', 1)
                ->latest('id')
                ->first();
        }

        if (! $resume) {
            return response()->json([
                'status' => false,
                'message' => 'A resume is required before applying to a job.',
            ], 422);
        }

        $coverLetterPath = null;

        if ($request->hasFile('cover_letter_file')) {
            $file = $request->file('cover_letter_file');
            $storedName = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());

            $coverLetterPath = $file->storeAs(
                'candidate-cover-letters/' . $profile->id,
                $storedName,
                'local'
            );
        }

        $application = DB::transaction(function () use ($job, $user, $profile, $resume, $validated, $coverLetterPath) {
            return JobApplication::create([
                'job_id' => $job->id,
                'candidate_user_id' => $user->id,
                'candidate_profile_id' => $profile->id,
                'resume_id' => $resume->id,
                'cover_letter_text' => $validated['cover_letter_text'] ?? null,
                'cover_letter_file_path' => $coverLetterPath,
                'status' => 'applied',
                'source' => $validated['source'] ?? 'portal',
                'applied_at' => now(),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Job application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $applications = JobApplication::with([
                'job:id,company_id,title,slug,location_city,location_state,location_country,job_type,experience_level,status,published_at',
                'job.company:id,name',
                'resume:id,file_name,version_no',
            ])
            ->where('candidate_user_id', $request->user()->id)
            ->latest('applied_at')
            ->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $applications,
        ]);
    }

    public function show(Request $request, int $applicationId): JsonResponse
    {
        $application = JobApplication::with([
                'job:id,company_id,title,slug,description,location_city,location_state,location_country,job_type,experience_level,status,published_at',
                'job.company:id,name,website',
                'resume:id,file_name,version_no,file_mime_type,file_size_bytes',
            ])
            ->where('candidate_user_id', $request->user()->id)
            ->findOrFail($applicationId);

        return response()->json([
            'status' => true,
            'data' => $application,
        ]);
    }
}