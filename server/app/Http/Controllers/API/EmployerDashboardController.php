<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = Company::where('owner_user_id', $user->id)->first();

        if (! $company) {
            return response()->json([
                'status' => true,
                'data' => [
                    'stats' => [
                        'active_jobs' => 0,
                        'applications_received' => 0,
                        'shortlisted_candidates' => 0,
                    ],
                    'recent_jobs' => [],
                    'recent_applications' => [],
                ],
            ]);
        }

        $jobIds = Job::where('company_id', $company->id)->pluck('id');

        $activeJobs = Job::where('company_id', $company->id)
            ->where('status', 'approved')
            ->count();

        $applicationsReceived = JobApplication::whereIn('job_id', $jobIds)->count();

        $shortlistedCandidates = JobApplication::whereIn('job_id', $jobIds)
            ->where('status', 'shortlisted')
            ->count();

        $recentJobs = Job::where('company_id', $company->id)
            ->latest('id')
            ->limit(5)
            ->get([
                'id',
                'title',
                'slug',
                'status',
                'approval_status',
                'published_at',
                'created_at',
            ]);

        $recentApplications = JobApplication::with([
                'candidate:id,first_name,middle_name,last_name,email',
                'job:id,title',
            ])
            ->whereIn('job_id', $jobIds)
            ->latest('applied_at')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'stats' => [
                    'active_jobs' => $activeJobs,
                    'applications_received' => $applicationsReceived,
                    'shortlisted_candidates' => $shortlistedCandidates,
                ],
                'recent_jobs' => $recentJobs,
                'recent_applications' => $recentApplications,
            ],
        ]);
    }
}