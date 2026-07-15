<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use Illuminate\Http\JsonResponse;

class PayrollPeriodController extends Controller
{
    public function store(StorePayrollPeriodRequest $request): JsonResponse
    {
        $data = $request->validated();
        // GAP-COMPANY: company_id is null until companies table exists
        $data['company_id'] = null;
        $data['status'] = 'open';

        $period = PayrollPeriod::create($data);

        return response()->json([
            'data' => [
                'id' => $period->id,
                'name' => $period->name,
                'status' => $period->status,
            ],
        ], 201);
    }
}
