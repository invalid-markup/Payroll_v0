<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeductionTypeRequest;
use App\Models\DeductionType;
use Illuminate\Http\JsonResponse;

class DeductionTypeController extends Controller
{
    public function store(StoreDeductionTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! isset($data['type'])) {
            $data['type'] = 'deduction';
        }

        $deductionType = DeductionType::create($data);

        return response()->json([
            'data' => [
                'id' => $deductionType->id,
                'code' => $deductionType->code,
                'name' => $deductionType->name,
                'type' => $deductionType->type,
            ],
        ], 201);
    }
}
