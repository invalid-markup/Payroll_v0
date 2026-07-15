<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\SuspendLoanRequest;
use App\Http\Requests\Loan\UpdateLoanInstallmentRequest;
use App\Http\Resources\Loan\LoanResource;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager', 'finance_manager', 'payroll_officer'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Loan::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        return response()->json([
            'data' => LoanResource::collection($query->get()),
        ]);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Ensure only one active loan per employee.
        $activeLoan = Loan::where('employee_id', $validated['employee_id'])
            ->whereIn('loan_status', ['active', 'suspended'])
            ->exists();

        if ($activeLoan) {
            return response()->json(['message' => 'Employee already has an active loan.'], 422);
        }

        $loan = Loan::create([
            'employee_id' => $validated['employee_id'],
            'principal_amount' => $validated['total_amount'],
            'installment_amount' => $validated['installment_amount'],
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        return response()->json([
            'data' => new LoanResource($loan),
        ], 201);
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['hr_manager', 'finance_manager', 'payroll_officer'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'data' => new LoanResource($loan),
        ]);
    }

    public function updateInstallment(UpdateLoanInstallmentRequest $request, Loan $loan): JsonResponse
    {
        $validated = $request->validated();

        $loan->update([
            'installment_amount' => $validated['installment_amount'],
        ]);

        return response()->json([
            'data' => new LoanResource($loan),
        ]);
    }

    public function suspend(SuspendLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan->update([
            'loan_status' => 'suspended',
        ]);

        return response()->json([
            'data' => new LoanResource($loan),
        ]);
    }

    public function close(Request $request, Loan $loan): JsonResponse
    {
        if (! $request->user()->hasAnyRole(['finance_manager'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $loan->update([
            'loan_status' => 'closed',
        ]);

        return response()->json([
            'data' => new LoanResource($loan),
        ]);
    }
}
