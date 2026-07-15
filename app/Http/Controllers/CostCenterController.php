<?php

namespace App\Http\Controllers;

use App\Http\Resources\Company\CostCenterResource;
use App\Models\CostCenter;

class CostCenterController extends Controller
{
    /**
     * GET /cost-centers — All authenticated users.
     * 07_API_SPECIFICATION.md §4.7
     */
    public function index()
    {
        return CostCenterResource::collection(CostCenter::paginate(50));
    }
}
