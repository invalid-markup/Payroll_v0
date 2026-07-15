<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollRun\StorePayrollRunRequest;
use App\Http\Resources\PayrollRun\PayrollRunResource;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $this->authorize('create', PayrollRun::class);

        $validated = $request->validated();

        $period = PayrollPeriod::findOrFail($validated['payroll_period_id']);

        $run = PayrollRun::create([
            'payroll_period_id' => $validated['payroll_period_id'],
            'company_id' => $period->company_id,
            'type' => $validated['type'],
            'status' => 'draft',
        ]);

        return response()->json([
            'data' => new PayrollRunResource($run),
        ], 201);
    }

    public function validateRun(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        $payrollRun->update(['status' => 'validated']);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function calculate(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        $payrollRun->update(['status' => 'preview']);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function getFlags(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('view', $payrollRun);

        // Return empty array for now as placeholder for validation flags
        return response()->json(['data' => []]);
    }

    public function submit(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('submit', $payrollRun);

        $payrollRun->update([
            'status' => 'preview',
            'submitted_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function approve(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        // Policy enforces Maker != Checker (§3.3 & §6.1)
        $this->authorize('approve', $payrollRun);

        $payrollRun->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function reject(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('approve', $payrollRun); // Checker can also reject

        $payrollRun->update(['status' => 'rejected']);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function file(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        // Locking: only allowed on approved runs
        if ($payrollRun->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved payroll runs can be filed.',
            ], 422);
        }

        $this->authorize('approve', $payrollRun); // Finance manager gates this

        $payrollRun->update(['status' => 'filed']);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function reverse(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        if (! $request->user()->hasRole('system_administrator')) {
            return response()->json(['message' => 'Only system administrators can reverse payroll runs.'], 403);
        }

        $payrollRun->update(['status' => 'reversed']);

        return response()->json(['data' => new PayrollRunResource($payrollRun)]);
    }

    public function amend(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['finance_manager', 'system_administrator'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Creates a new draft run of type 'amended_return' linked to the original
        $amended = PayrollRun::create([
            'payroll_period_id' => $payrollRun->payroll_period_id,
            'company_id' => $payrollRun->company_id,
            'type' => 'amended_return',
            'status' => 'draft',
            'original_run_id' => $payrollRun->id,
        ]);

        return response()->json(['data' => new PayrollRunResource($amended)]);
    }
}
