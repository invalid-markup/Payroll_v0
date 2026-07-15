<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\StoreLeaveRecordRequest;
use App\Http\Requests\Leave\UpdateLeaveRecordRequest;
use App\Http\Resources\Leave\LeaveRecordResource;
use App\Models\LeaveRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager', 'hr_officer'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = LeaveRecord::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        return response()->json([
            'data' => LeaveRecordResource::collection($query->get()),
        ]);
    }

    public function store(StoreLeaveRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $leave = LeaveRecord::create([
            'employee_id' => $validated['employee_id'],
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $validated['days'],
        ]);

        return response()->json([
            'data' => new LeaveRecordResource($leave),
        ], 201);
    }

    public function update(UpdateLeaveRecordRequest $request, LeaveRecord $leaveRecord): JsonResponse
    {
        $validated = $request->validated();

        $dataToUpdate = [];
        if (isset($validated['leave_type'])) {
            $dataToUpdate['leave_type'] = $validated['leave_type'];
        }
        if (isset($validated['start_date'])) {
            $dataToUpdate['start_date'] = $validated['start_date'];
        }
        if (isset($validated['end_date'])) {
            $dataToUpdate['end_date'] = $validated['end_date'];
        }
        if (isset($validated['days'])) {
            $dataToUpdate['total_days'] = $validated['days'];
        }

        $leaveRecord->update($dataToUpdate);

        return response()->json([
            'data' => new LeaveRecordResource($leaveRecord),
        ]);
    }

    public function destroy(Request $request, LeaveRecord $leaveRecord): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager', 'hr_officer'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $leaveRecord->delete();

        return response()->json([], 204);
    }
}
