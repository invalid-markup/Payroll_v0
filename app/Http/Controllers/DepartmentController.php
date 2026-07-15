<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreDepartmentRequest;
use App\Http\Resources\Company\DepartmentResource;
use App\Models\Department;

class DepartmentController extends Controller
{
    /**
     * GET /departments — All authenticated users.
     * 07_API_SPECIFICATION.md §4.5
     */
    public function index()
    {
        return DepartmentResource::collection(Department::paginate(50));
    }

    /**
     * POST /departments — System Administrator only.
     * 07_API_SPECIFICATION.md §4.6
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }
}
