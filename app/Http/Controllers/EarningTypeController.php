<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEarningTypeRequest;
use App\Models\EarningType;
use Illuminate\Http\JsonResponse;

class EarningTypeController extends Controller
{
    public function store(StoreEarningTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! isset($data['type'])) {
            $data['type'] = 'allowance';
        }

        $earningType = EarningType::create($data);

        return response()->json([
            'data' => [
                'id' => $earningType->id,
                'code' => $earningType->code,
                'name' => $earningType->name,
                'type' => $earningType->type,
            ],
        ], 201);
    }
}
