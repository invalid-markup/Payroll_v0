<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StorePublicHolidayRequest;
use App\Http\Resources\Company\PublicHolidayResource;
use App\Models\PublicHoliday;
use Illuminate\Support\Str;

class PublicHolidayController extends Controller
{
    /**
     * GET /public-holidays — All authenticated users.
     * 07_API_SPECIFICATION.md §4.8
     */
    public function index()
    {
        // Scope to current company extracted from token
        $companyId = $this->getCompanyIdFromToken(request());

        $holidays = PublicHoliday::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('date')
            ->paginate(50);

        return PublicHolidayResource::collection($holidays);
    }

    /**
     * POST /public-holidays — System Administrator only.
     * 07_API_SPECIFICATION.md §4.9
     */
    public function store(StorePublicHolidayRequest $request)
    {
        $companyId = $this->getCompanyIdFromToken($request);

        $holiday = PublicHoliday::create([
            'id' => Str::uuid(),
            'company_id' => $companyId,
            'date' => $request->input('date'),
            'name' => $request->input('name'),
        ]);

        return (new PublicHolidayResource($holiday))->response()->setStatusCode(201);
    }

    /**
     * Extract company_id from the token ability (format: company:UUID).
     */
    private function getCompanyIdFromToken($request): ?string
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token) {
            return null;
        }

        foreach ($token->abilities as $ability) {
            if (str_starts_with($ability, 'company:')) {
                return substr($ability, 8);
            }
        }

        return null;
    }
}
