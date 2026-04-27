<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApplicationInterview;
use App\Models\ApplicationStatusLog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerApplicantController extends Controller
{
    public function listByJob(Request $request, int $jobId): JsonResponse
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();
        $job = Job::where('company_id', $company->id)->findOrFail($jobId);

        $applications = JobApplication::with([
                'candidate:id,first_name,middle_name,last_name,email,phone',
                'candidateProfile:id,user_id,headline,summary',
                'resume:id,file_name,file_mime_type,file_size_bytes,version_no,storage_disk,file_path',
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
                'interviews',
                'statusLogs.changedBy:id,first_name,last_name,email',
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
        $oldStatus = $application->status;

        $application->update([
            'status' => 'shortlisted',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        $this->logStatusChange(
            application: $application,
            changedByUserId: $request->user()->id,
            oldStatus: $oldStatus,
            newStatus: 'shortlisted',
            remarks: 'Candidate shortlisted by employer.'
        );

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
        $oldStatus = $application->status;

        $application->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $this->logStatusChange(
            application: $application,
            changedByUserId: $request->user()->id,
            oldStatus: $oldStatus,
            newStatus: 'rejected',
            remarks: $validated['rejection_reason'] ?? 'Candidate rejected by employer.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Candidate rejected successfully.',
            'data' => $application->fresh(),
        ]);
    }

    public function interview(Request $request, int $applicationId): JsonResponse
    {
        $validated = $request->validate([
            'interview_at' => ['required', 'date'],
            'interview_mode' => ['nullable', 'in:online,on_site,phone'],
            'interview_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $application = $this->findOwnedApplication($request, $applicationId);
        $oldStatus = $application->status;

        $interview = DB::transaction(function () use ($request, $validated, $application, $oldStatus) {
            $interview = ApplicationInterview::create([
                'job_application_id' => $application->id,
                'scheduled_by_user_id' => $request->user()->id,
                'interview_at' => $validated['interview_at'],
                'interview_mode' => $validated['interview_mode'] ?? null,
                'interview_location' => $validated['interview_location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'scheduled',
            ]);

            $application->update([
                'status' => 'interview_scheduled',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $request->user()->id,
            ]);

            $this->logStatusChange(
                application: $application,
                changedByUserId: $request->user()->id,
                oldStatus: $oldStatus,
                newStatus: 'interview_scheduled',
                remarks: $validated['notes'] ?? 'Interview scheduled by employer.'
            );

            return $interview;
        });

        return response()->json([
            'status' => true,
            'message' => 'Interview scheduled successfully.',
            'data' => $interview,
        ]);
    }

    public function downloadResume(Request $request, int $applicationId): StreamedResponse
    {
        $application = $this->findOwnedApplication($request, $applicationId)->load('resume');

        if (! $application->resume) {
            abort(404, 'Resume not found for this application.');
        }

        $disk = $application->resume->storage_disk ?: 'local';
        $path = $application->resume->file_path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Resume file does not exist.');
        }

        return Storage::disk($disk)->download(
            $path,
            $application->resume->file_name
        );
    }

    private function findOwnedApplication(Request $request, int $applicationId): JobApplication
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();

        return JobApplication::whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($applicationId);
    }

    private function logStatusChange(
        JobApplication $application,
        int $changedByUserId,
        ?string $oldStatus,
        string $newStatus,
        ?string $remarks
    ): void {
        ApplicationStatusLog::create([
            'job_application_id' => $application->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $changedByUserId,
            'remarks' => $remarks,
            'created_at' => now(),
        ]);
    }
}