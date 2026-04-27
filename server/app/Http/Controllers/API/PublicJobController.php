<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Job::with('company:id,name')
            ->publiclyVisible()
            ->latest('published_at');

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('location')) {
            $location = trim((string) $request->location);

            $query->where(function ($q) use ($location) {
                $q->where('location_city', 'like', "%{$location}%")
                  ->orWhere('location_state', 'like', "%{$location}%")
                  ->orWhere('location_country', 'like', "%{$location}%");
            });
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->filled('work_mode')) {
            $query->where('work_mode', $request->work_mode);
        }

        if ($request->filled('salary_min')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('salary_max')
                  ->orWhere('salary_max', '>=', $request->salary_min);
            });
        }

        $jobs = $query->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $jobs,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $job = Job::with('company:id,name,website,location_city,location_state,location_country')
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $job,
        ]);
    }
}