<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryStructure\StoreSalaryStructureRequest;
use App\Http\Resources\SalaryStructure\SalaryStructureResource;
use App\Models\SalaryStructure;

class SalaryStructureController extends Controller
{
    /**
     * GET /salary-structures
     * 07_API_SPECIFICATION.md §6.1
     */
    public function index()
    {
        return SalaryStructureResource::collection(SalaryStructure::paginate(50));
    }

    /**
     * POST /salary-structures
     * 07_API_SPECIFICATION.md §6.2
     */
    public function store(StoreSalaryStructureRequest $request)
    {
        $structure = SalaryStructure::create([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'currency' => 'TZS',
        ]);

        return (new SalaryStructureResource($structure))->response()->setStatusCode(201);
    }
}
