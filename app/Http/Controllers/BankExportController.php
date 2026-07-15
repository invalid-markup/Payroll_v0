<?php

namespace App\Http\Controllers;

use App\Models\BankExport;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BankExportController extends Controller
{
    public function validateData(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['system_administrator', 'finance_manager'])) {
            abort(403);
        }
        $request->validate([
            'payroll_period_id' => 'required|uuid|exists:payroll_periods,id',
        ]);

        $period = PayrollPeriod::findOrFail($request->input('payroll_period_id'));
        $run = $period->payrollRuns()->where('status', 'approved')->first();

        if (! $run) {
            return response()->json([
                'is_valid' => false,
                'missing_bank_details' => [],
                'message' => 'No approved payroll run found for this period.',
            ], 422);
        }

        // Check for employees without bank details
        $missingBank = PayrollRunResult::where('payroll_run_id', $run->id)
            ->with('employee.bankDetails')
            ->get()
            ->filter(fn ($r) => $r->employee->bankDetails->isEmpty())
            ->map(fn ($r) => $r->employee_id)
            ->values();

        return response()->json([
            'is_valid' => $missingBank->isEmpty(),
            'missing_bank_details' => $missingBank,
        ]);
    }

    public function download(Request $request): Response|JsonResponse
    {
        if (! $request->user()->hasAnyRole(['system_administrator', 'finance_manager'])) {
            abort(403);
        }

        $request->validate([
            'payroll_period_id' => 'required|uuid|exists:payroll_periods,id',
        ]);

        $period = PayrollPeriod::findOrFail($request->input('payroll_period_id'));
        $run = $period->payrollRuns()->where('status', 'approved')->first();

        if (! $run) {
            return response()->json(['message' => 'No approved payroll run found for this period.'], 422);
        }

        // Build CSV rows
        $results = PayrollRunResult::where('payroll_run_id', $run->id)
            ->with('employee.bankDetails')
            ->get();

        $rows = [];
        $rows[] = implode(',', ['Employee Number', 'Account Name', 'Account Number', 'Bank Code', 'Branch Code', 'Amount']);

        $totalAmount = '0.0000';

        foreach ($results as $result) {
            $emp = $result->employee;
            $bank = $emp->bankDetails->first();

            $rows[] = implode(',', [
                $emp->employee_number ?? '',
                trim(($emp->first_name ?? '').' '.($emp->last_name ?? '')),
                $bank->account_number ?? '',
                $bank->bank_code ?? '',
                $bank->branch_code ?? '',
                number_format((float) $result->net_salary_amount, 2, '.', ''),
            ]);

            $totalAmount = bcadd($totalAmount, (string) $result->net_salary_amount, 4);
        }

        $csvContent = implode("\n", $rows);

        // SHA-256 hash of the file content (§6.4)
        $fileHash = hash('sha256', $csvContent);

        // Store the Hard Record
        BankExport::create([
            'payroll_run_id' => $run->id,
            'generated_by_user_id' => $request->user()->id,
            'file_hash' => $fileHash,
            'total_records' => $results->count(),
            'total_amount' => $totalAmount,
        ]);

        return response($csvContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="bank_export_'.$run->id.'.csv"')
            ->header('X-File-Hash', $fileHash);
    }
}
