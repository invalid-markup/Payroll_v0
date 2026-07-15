<?php

namespace App\Http\Controllers;

use App\Http\Resources\Payslip\PayslipResource;
use App\Models\PayrollRunResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Simple authorization stub; actual checks would verify if employee owns it or is admin
        $query = PayrollRunResult::query()->with('payrollRun');

        if ($request->has('payroll_period_id')) {
            $query->whereHas('payrollRun', function ($q) use ($request) {
                $q->where('payroll_period_id', $request->input('payroll_period_id'));
            });
        }

        if ($request->has('employee_id')) {
            if (! $request->user()->hasAnyRole(['hr_manager', 'finance_manager', 'payroll_officer', 'auditor', 'system_administrator'])) {
                // If not admin, can only view own (this logic would use the user's mapped employee profile)
                // For simplicity, just return 403 if they try to query someone else explicitly and aren't admin.
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
            $query->where('employee_id', $request->input('employee_id'));
        }

        return response()->json([
            'data' => PayslipResource::collection($query->get()),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $payslip = PayrollRunResult::with('payrollRun')->findOrFail($id);

        return response()->json([
            'data' => new PayslipResource($payslip),
        ]);
    }

    public function export(Request $request, string $id)
    {
        $payslip = PayrollRunResult::findOrFail($id);

        // Returns a dummy PDF response
        return response('Dummy PDF content for '.$payslip->id, 200)
            ->header('Content-Type', 'application/pdf');
    }
}
