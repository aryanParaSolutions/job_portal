<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApprovalLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobModerationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $jobs = Job::with('company:id,name', 'postedBy:id,first_name,last_name,email')
            ->where('status', 'pending_approval')
            ->where('approval_status', 'pending')
            ->latest('id')
            ->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $jobs,
        ]);
    }

    public function show(int $jobId): JsonResponse
    {
        $job = Job::with([
                'company',
                'postedBy:id,first_name,last_name,email,phone,status',
                'approvalLogs.actionBy:id,first_name,last_name,email',
            ])
            ->findOrFail($jobId);

        $applicationsCount = $job->applications()->count();

        return response()->json([
            'status' => true,
            'data' => [
                'job' => $job,
                'applications_count' => $applicationsCount,
            ],
        ]);
    }

    public function approve(Request $request, int $jobId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $job = Job::findOrFail($jobId);

        if ($job->status === 'approved' && $job->approval_status === 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Job is already approved.',
            ], 422);
        }

        DB::transaction(function () use ($request, $validated, $job) {
            $oldStatus = $job->status;

            $job->update([
                'status' => 'approved',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'rejection_reason' => null,
                'published_at' => $job->published_at ?: now(),
            ]);

            JobApprovalLog::create([
                'job_id' => $job->id,
                'action_by_user_id' => $request->user()->id,
                'action' => 'approved',
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'remarks' => $validated['remarks'] ?? 'Job approved by admin.',
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Job approved successfully.',
            'data' => Job::find($jobId),
        ]);
    }

    public function reject(Request $request, int $jobId): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string'],
        ]);

        $job = Job::findOrFail($jobId);

        DB::transaction(function () use ($request, $validated, $job) {
            $oldStatus = $job->status;

            $job->update([
                'status' => 'rejected',
                'approval_status' => 'rejected',
                'rejection_reason' => $validated['remarks'],
            ]);

            JobApprovalLog::create([
                'job_id' => $job->id,
                'action_by_user_id' => $request->user()->id,
                'action' => 'rejected',
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'remarks' => $validated['remarks'],
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Job rejected successfully.',
            'data' => Job::find($jobId),
        ]);
    }
}