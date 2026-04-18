<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerApplicantController extends Controller
{
    public function listByJob(Request $request, int $jobId): JsonResponse
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();
        $job = Job::where('company_id', $company->id)->findOrFail($jobId);

        $applications = JobApplication::with([
                'candidate:id,first_name,middle_name,last_name,email,phone',
                'candidateProfile:id,user_id,headline,summary',
                'resume:id,file_name,file_mime_type,file_size_bytes,version_no',
            ])
            ->where('job_id', $job->id)
            ->latest('applied_at')
            ->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $applications,
        ]);
    }

    public function show(Request $request, int $applicationId): JsonResponse
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();

        $application = JobApplication::with([
                'candidate:id,first_name,middle_name,last_name,email,phone',
                'candidateProfile',
                'resume',
                'job:id,company_id,title,slug,status,approval_status',
            ])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($applicationId);

        return response()->json([
            'status' => true,
            'data' => $application,
        ]);
    }

    public function shortlist(Request $request, int $applicationId): JsonResponse
    {
        $application = $this->findOwnedApplication($request, $applicationId);

        $application->update([
            'status' => 'shortlisted',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Candidate shortlisted successfully.',
            'data' => $application->fresh(),
        ]);
    }

    public function reject(Request $request, int $applicationId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $application = $this->findOwnedApplication($request, $applicationId);

        $application->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Candidate rejected successfully.',
            'data' => $application->fresh(),
        ]);
    }

    private function findOwnedApplication(Request $request, int $applicationId): JobApplication
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();

        return JobApplication::whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($applicationId);
    }
}