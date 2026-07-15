<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreBranchRequest;
use App\Http\Resources\Company\BranchResource;
use App\Models\Branch;

class BranchController extends Controller
{
    /**
     * GET /branches — All authenticated users.
     * 07_API_SPECIFICATION.md §4.3
     */
    public function index()
    {
        return BranchResource::collection(Branch::paginate(50));
    }

    /**
     * POST /branches — System Administrator only.
     * 07_API_SPECIFICATION.md §4.4
     */
    public function store(StoreBranchRequest $request)
    {
        $branch = Branch::create($request->validated());

        return new BranchResource($branch);
    }
}
