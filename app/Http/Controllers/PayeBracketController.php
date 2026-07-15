<?php

namespace App\Http\Controllers;

use App\Http\Requests\Statutory\StoreBulkPayeBracketsRequest;
use App\Http\Resources\Statutory\PayeBracketResource;
use App\Models\PayeBracket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayeBracketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['system_administrator', 'auditor', 'payroll_officer', 'finance_manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $date = $request->input('date', now()->format('Y-m-d'));

        // To get the active set of brackets, we need to find the latest effective_from date <= requested date,
        // and then fetch all brackets with that exact effective_from date.
        $latestDate = PayeBracket::where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->value('effective_from');

        if (! $latestDate) {
            return response()->json(['data' => []]);
        }

        $brackets = PayeBracket::where('effective_from', $latestDate)
            ->orderBy('minimum_income')
            ->get();

        return response()->json([
            'data' => PayeBracketResource::collection($brackets),
        ]);
    }

    public function storeBulk(StoreBulkPayeBracketsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $effectiveFrom = $validated['effective_from'];

        // DB Transaction to insert all brackets for the effective date
        DB::transaction(function () use ($validated, $effectiveFrom) {
            foreach ($validated['brackets'] as $bracketData) {
                PayeBracket::create([
                    'minimum_income' => $bracketData['minimum_income'],
                    'maximum_income' => $bracketData['maximum_income'],
                    'rate_percentage' => $bracketData['rate_percentage'],
                    'base_tax_amount' => $bracketData['base_tax_amount'],
                    'effective_from' => $effectiveFrom,
                ]);
            }
        });

        return response()->json(['message' => 'PAYE brackets created successfully.'], 201);
    }
}
