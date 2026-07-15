<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAbsenceRequest;
use App\Http\Requests\Attendance\StoreOvertimeRequest;
use App\Http\Resources\Attendance\AttendanceRecordResource;
use App\Models\LeaveRecord;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager', 'hr_officer'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $overtimes = OvertimeRecord::query();
        $absences = LeaveRecord::where('leave_type', 'unauthorized_absence');

        if ($request->has('employee_id')) {
            $overtimes->where('employee_id', $request->input('employee_id'));
            $absences->where('employee_id', $request->input('employee_id'));
        }

        $records = $overtimes->get()->merge($absences->get());

        return response()->json([
            'data' => AttendanceRecordResource::collection($records),
        ]);
    }

    public function storeOvertime(StoreOvertimeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date']);

        $payrollPeriod = PayrollPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if (! $payrollPeriod) {
            return response()->json(['message' => 'No active payroll period found for the given date.'], 422);
        }

        if ($payrollPeriod->status !== 'open') {
            return response()->json(['message' => 'Payroll period is closed.'], 422);
        }

        $overtime = OvertimeRecord::create([
            'employee_id' => $validated['employee_id'],
            'payroll_period_id' => $payrollPeriod->id,
            'overtime_type' => isset($validated['fixed_amount']) ? 'fixed_amount' : 'hours_based',
            'hours' => $validated['hours'] ?? null,
            'fixed_amount' => $validated['fixed_amount'] ?? null,
        ]);

        return response()->json([
            'data' => new AttendanceRecordResource($overtime),
        ], 201);
    }

    public function storeAbsence(StoreAbsenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date']);
        $days = $validated['days'];
        $endDate = $date->copy()->addDays($days - 1);

        $absence = LeaveRecord::create([
            'employee_id' => $validated['employee_id'],
            'leave_type' => 'unauthorized_absence',
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => $days,
        ]);

        return response()->json([
            'data' => new AttendanceRecordResource($absence),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $overtime = OvertimeRecord::find($id);
        if ($overtime) {
            $overtime->delete();

            return response()->json([], 204);
        }

        $absence = LeaveRecord::where('leave_type', 'unauthorized_absence')->find($id);
        if ($absence) {
            $absence->delete();

            return response()->json([], 204);
        }

        return response()->json(['message' => 'Record not found.'], 404);
    }
}
