<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Auth::user()->company_id;
        $statusFilter = $request->get('status', 'all');

        $counts = PayrollRun::where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $counts['all'] = array_sum($counts);

        $runs = PayrollRun::where('company_id', $companyId)
            ->with(['payrollPeriod', 'processedBy', 'results.employee'])
            ->when($statusFilter !== 'all', fn ($query) => $query->where('status', $statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('payroll-runs.index', compact('runs', 'counts'));
    }

    public function show(string $id): View
    {
        $run = $this->loadRun($id);

        return view('payroll-runs.show', [
            'run' => $run,
            'flags' => $this->buildValidationFlags($run),
        ]);
    }

    public function validateScreen(string $id): View
    {
        $run = $this->loadRun($id);

        return view('payroll-runs.validate', [
            'run' => $run,
            'flags' => $this->buildValidationFlags($run),
        ]);
    }

    public function create(): View
    {
        $companyId = Auth::user()->company_id;

        $periods = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('payroll-runs.create', compact('periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payroll_period_id' => ['required', 'uuid', 'exists:payroll_periods,id'],
            'type' => ['required', 'in:standard,supplementary'],
        ]);

        $period = PayrollPeriod::where('company_id', Auth::user()->company_id)
            ->findOrFail($validated['payroll_period_id']);

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'company_id' => $period->company_id,
            'type' => $validated['type'],
            'status' => 'draft',
        ]);

        return redirect()
            ->route('payroll-runs.validate', $run->id)
            ->with('success', 'Payroll run created. Review the pre-flight checks before calculating.');
    }

    public function validateRun(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if (! in_array($run->status, ['draft', 'validated'], true)) {
            return back()->with('error', 'Only draft payroll runs can be validated.');
        }

        $run->update([
            'status' => 'validated',
            'metadata' => array_merge($run->metadata ?? [], [
                'validated_at' => now()->toIso8601String(),
                'validated_by' => $request->user()->name,
            ]),
        ]);

        return redirect()
            ->route('payroll-runs.validate', $run->id)
            ->with('success', 'Payroll run marked as validated.');
    }

    public function calculate(Request $request, string $id, PayrollCalculationService $service): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'validated') {
            return back()->with('error', 'Please validate the payroll run before calculating it.');
        }

        $service->calculate($run->id);

        return redirect()
            ->route('payroll-runs.show', $run->id)
            ->with('success', 'Payroll run calculated successfully.');
    }

    public function submit(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'preview') {
            return back()->with('error', 'Only preview payroll runs can be submitted for approval.');
        }

        $run->update([
            'status' => 'approved',
            'submitted_by_user_id' => $request->user()->id,
            'metadata' => array_merge($run->metadata ?? [], [
                'submitted_at' => now()->toIso8601String(),
                'submitted_by' => $request->user()->name,
            ]),
        ]);

        return redirect()
            ->route('payroll-runs.show', $run->id)
            ->with('success', 'Payroll run submitted for approval.');
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'approved') {
            return back()->with('error', 'Only approved payroll runs can be locked.');
        }

        if ($request->user()->id === $run->submitted_by_user_id) {
            return back()->with('error', 'You cannot approve a payroll run you submitted.');
        }

        $run->update([
            'status' => 'locked',
            'approved_by_user_id' => $request->user()->id,
            'metadata' => array_merge($run->metadata ?? [], [
                'approved_at' => now()->toIso8601String(),
                'approved_by' => $request->user()->name,
            ]),
        ]);

        return redirect()
            ->route('payroll-runs.show', $run->id)
            ->with('success', 'Payroll run locked successfully.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'approved') {
            return back()->with('error', 'Only approved payroll runs can be rejected.');
        }

        if ($request->user()->id === $run->submitted_by_user_id) {
            return back()->with('error', 'You cannot reject a payroll run you submitted.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $run->update([
            'status' => 'draft',
            'metadata' => array_merge($run->metadata ?? [], [
                'rejected_at' => now()->toIso8601String(),
                'rejected_by' => $request->user()->name,
                'rejection_reason' => $validated['rejection_reason'],
            ]),
        ]);

        return redirect()
            ->route('payroll-runs.show', $run->id)
            ->with('warning', 'Payroll run rejected and returned to draft.');
    }

    public function file(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'locked') {
            return back()->with('error', 'Only locked payroll runs can be filed.');
        }

        $run->update([
            'status' => 'filed',
            'metadata' => array_merge($run->metadata ?? [], [
                'filed_at' => now()->toIso8601String(),
                'filed_by' => $request->user()->name,
            ]),
        ]);

        return redirect()
            ->route('payroll-runs.show', $run->id)
            ->with('success', 'Payroll run filed successfully.');
    }

    public function amend(Request $request, string $id): RedirectResponse
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'filed') {
            return back()->with('error', 'Only filed payroll runs can be amended.');
        }

        $amended = PayrollRun::create([
            'payroll_period_id' => $run->payroll_period_id,
            'company_id' => $run->company_id,
            'type' => 'amended_return',
            'status' => 'draft',
            'original_run_id' => $run->id,
            'metadata' => [
                'amended_from' => $run->id,
                'amended_at' => now()->toIso8601String(),
            ],
        ]);

        return redirect()
            ->route('payroll-runs.validate', $amended->id)
            ->with('success', 'Amended payroll run created.');
    }

    private function loadRun(string $id): PayrollRun
    {
        return PayrollRun::where('company_id', Auth::user()->company_id)
            ->with([
                'payrollPeriod',
                'processedBy',
                'approvedBy',
                'originalRun',
                'results.employee',
                'results.lineItems',
            ])
            ->findOrFail($id);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildValidationFlags(PayrollRun $run): array
    {
        $flags = [];

        if ($run->results->isEmpty()) {
            $flags[] = [
                'severity' => 'warning',
                'title' => 'No payroll entries generated yet',
                'message' => 'Run calculation to populate payroll entries for this period.',
            ];
        }

        $failedEntries = $run->results->where('processing_status', 'failed');
        if ($failedEntries->isNotEmpty()) {
            $flags[] = [
                'severity' => 'error',
                'title' => 'Failed employee processing',
                'message' => $failedEntries->count().' employee(s) were marked as failed and need attention.',
            ];
        }

        if ($run->type === 'amended_return' && ! $run->originalRun) {
            $flags[] = [
                'severity' => 'error',
                'title' => 'Missing original run link',
                'message' => 'Amended returns must reference the filed run they are correcting.',
            ];
        }

        if ($run->status === 'approved') {
            $flags[] = [
                'severity' => 'info',
                'title' => 'Ready for checker review',
                'message' => 'This run can now be approved and locked by an authorized checker.',
            ];
        }

        return $flags;
    }
}