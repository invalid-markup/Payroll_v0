<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\CompanyProfile;

class CompanyController extends Controller
{
    /**
     * GET /company — All authenticated users.
     * 07_API_SPECIFICATION.md §4.1
     */
    public function show()
    {
        // Return the company profile for the current tenant
        $profile = CompanyProfile::first();

        if (! $profile) {
            return response()->json(['message' => 'Company profile not found.'], 404);
        }

        return new CompanyResource($profile);
    }

    /**
     * PUT /company — System Administrator only.
     * 07_API_SPECIFICATION.md §4.2
     */
    public function update(UpdateCompanyRequest $request)
    {
        $profile = CompanyProfile::first();

        if (! $profile) {
            // Create the profile if it doesn't exist (first-time bootstrap)
            $profile = new CompanyProfile;
        }

        $profile->fill([
            'company_name' => $request->input('name'),
            'tin' => $request->input('tin'),
            'registration_number' => $request->input('registration_number'),
            'address' => $request->input('address'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'working_days_per_month' => $request->input('working_days_per_month'),
            'financial_year_start_month' => $request->input('financial_year_start_month', 1),
            'sdl_enabled' => $request->boolean('sdl_enabled', true),
            'wcf_enabled' => $request->boolean('wcf_enabled', true),
            'sdl_employee_threshold' => $request->input('sdl_employee_threshold', 4),
        ])->save();

        return new CompanyResource($profile);
    }
}
