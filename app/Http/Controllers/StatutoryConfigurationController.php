<?php

namespace App\Http\Controllers;

use App\Http\Requests\Statutory\StoreStatutoryConfigurationRequest;
use App\Http\Resources\Statutory\StatutoryConfigurationResource;
use App\Models\StatutoryConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatutoryConfigurationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Require specific roles per API Spec Module 9.1
        if (! $request->user()->hasAnyRole(['system_administrator', 'auditor', 'payroll_officer', 'finance_manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $date = $request->input('date', now()->format('Y-m-d'));

        // For each code (nssf_employer, nssf_employee, wcf, sdl), get the latest active record <= $date
        $codes = ['nssf_employer', 'nssf_employee', 'wcf', 'sdl'];
        $activeConfigs = [];

        foreach ($codes as $code) {
            $config = StatutoryConfiguration::where('code', $code)
                ->where('effective_from', '<=', $date)
                ->orderByDesc('effective_from')
                ->first();

            if ($config) {
                $activeConfigs[] = $config;
            }
        }

        return response()->json([
            'data' => StatutoryConfigurationResource::collection($activeConfigs),
        ]);
    }

    public function store(StoreStatutoryConfigurationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $config = StatutoryConfiguration::create([
            'code' => $validated['code'],
            'name' => strtoupper($validated['code']), // Temporary mapping, would ideally be provided
            'rate_percentage' => $validated['rate_percentage'],
            'effective_from' => $validated['effective_from'],
        ]);

        return response()->json([
            'data' => new StatutoryConfigurationResource($config),
        ], 201);
    }
}
