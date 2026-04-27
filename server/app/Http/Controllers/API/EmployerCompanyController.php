<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployerCompanyController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $company = Company::where('owner_user_id', $user->id)->first();
        $employerProfile = EmployerProfile::where('user_id', $user->id)->first();

        return response()->json([
            'status' => true,
            'data' => [
                'company' => $company,
                'employer_profile' => $employerProfile,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_primary_contact' => ['nullable', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($user, $validated) {
            $company = Company::firstOrNew([
                'owner_user_id' => $user->id,
            ]);

            $company->fill([
                'industry_id' => $validated['industry_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'website' => $validated['website'] ?? null,
                'contact_email' => $validated['contact_email'] ?? $user->email,
                'contact_phone' => $validated['contact_phone'] ?? $user->phone,
                'location_city' => $validated['location_city'] ?? null,
                'location_state' => $validated['location_state'] ?? null,
                'location_country' => $validated['location_country'] ?? null,
                'address_line_1' => $validated['address_line_1'] ?? null,
                'address_line_2' => $validated['address_line_2'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'status' => $company->status ?: 'active',
                'verification_status' => $company->verification_status ?: 'pending',
            ]);

            $company->save();

            $employerProfile = EmployerProfile::firstOrNew([
                'user_id' => $user->id,
            ]);

            $employerProfile->fill([
                'company_id' => $company->id,
                'designation' => $validated['designation'] ?? null,
                'department' => $validated['department'] ?? null,
                'is_primary_contact' => (bool) ($validated['is_primary_contact'] ?? true),
            ]);

            $employerProfile->save();

            return [
                'company' => $company->fresh(),
                'employer_profile' => $employerProfile->fresh(),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Company profile updated successfully.',
            'data' => $result,
        ]);
    }
}