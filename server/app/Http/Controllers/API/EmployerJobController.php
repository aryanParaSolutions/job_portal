<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployerJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = Company::where('owner_user_id', $request->user()->id)->firstOrFail();

        $jobs = Job::where('company_id', $company->id)
            ->latest('id')
            ->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $jobs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = Company::where('owner_user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'work_mode' => ['nullable', 'in:remote,on_site,hybrid'],
            'experience_level' => ['nullable', 'in:junior,mid,senior'],
            'job_type' => ['required', 'in:full_time,part_time,internship'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'application_deadline' => ['nullable', 'date'],
        ]);

        $slug = $this->makeUniqueSlug($validated['title']);

        $job = Job::create([
            'company_id' => $company->id,
            'posted_by_user_id' => $user->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'currency_code' => $validated['currency_code'] ?? 'INR',
            'location_city' => $validated['location_city'] ?? null,
            'location_state' => $validated['location_state'] ?? null,
            'location_country' => $validated['location_country'] ?? null,
            'work_mode' => $validated['work_mode'] ?? null,
            'experience_level' => $validated['experience_level'] ?? null,
            'job_type' => $validated['job_type'],
            'vacancies' => $validated['vacancies'] ?? 1,
            'application_deadline' => $validated['application_deadline'] ?? null,
            'status' => 'draft',
            'approval_status' => 'pending',
            'is_flagged' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Job created successfully.',
            'data' => $job,
        ], 201);
    }

    public function update(Request $request, int $jobId): JsonResponse
    {
        $user = $request->user();
        $company = Company::where('owner_user_id', $user->id)->firstOrFail();

        $job = Job::where('company_id', $company->id)->findOrFail($jobId);

        if (! in_array($job->status, ['draft', 'rejected'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Only draft or rejected jobs can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'description' => ['sometimes', 'required', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'work_mode' => ['nullable', 'in:remote,on_site,hybrid'],
            'experience_level' => ['nullable', 'in:junior,mid,senior'],
            'job_type' => ['sometimes', 'required', 'in:full_time,part_time,internship'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'application_deadline' => ['nullable', 'date'],
        ]);

        if (! empty($validated['title']) && $validated['title'] !== $job->title) {
            $validated['slug'] = $this->makeUniqueSlug($validated['title'], $job->id);
        }

        $job->fill($validated);
        $job->save();

        return response()->json([
            'status' => true,
            'message' => 'Job updated successfully.',
            'data' => $job->fresh(),
        ]);
    }

    public function publish(Request $request, int $jobId): JsonResponse
    {
        $user = $request->user();
        $company = Company::where('owner_user_id', $user->id)->firstOrFail();

        $job = Job::where('company_id', $company->id)->findOrFail($jobId);

        if (! in_array($job->status, ['draft', 'rejected'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Only draft or rejected jobs can be submitted for approval.',
            ], 422);
        }

        if (! $this->companyProfileComplete($company)) {
            return response()->json([
                'status' => false,
                'message' => 'Complete company profile before publishing a job.',
            ], 422);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Only active employers can publish jobs.',
            ], 403);
        }

        $job->update([
            'status' => 'pending_approval',
            'approval_status' => 'pending',
            'rejection_reason' => null,
            'published_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Job submitted for approval successfully.',
            'data' => $job->fresh(),
        ]);
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (
            Job::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function companyProfileComplete(Company $company): bool
    {
        return filled($company->name)
            && filled($company->description)
            && filled($company->contact_email)
            && filled($company->location_city)
            && filled($company->location_state)
            && filled($company->location_country);
    }
}