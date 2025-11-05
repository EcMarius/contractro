<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FinancialReport;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    protected $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
    }

    /**
     * Display dashboard with financial summary
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            $firstCompany = Company::where('user_id', $user->id)->first();
            if (!$firstCompany) {
                return redirect()->route('companies.create')
                    ->with('info', 'Vă rugăm să creați mai întâi o companie.');
            }
            $companyId = $firstCompany->id;
            session(['active_company_id' => $companyId]);
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        // Get dashboard summary
        $summary = $this->reportService->getDashboardSummary($companyId);

        return view('theme::reports.dashboard', compact('company', 'summary'));
    }

    /**
     * Display a listing of financial reports
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Vă rugăm să selectați o companie.');
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        $reportType = $request->input('type');

        $query = FinancialReport::where('company_id', $companyId);

        if ($reportType) {
            $query->where('report_type', $reportType);
        }

        $reports = $query->latest('generated_at')->paginate(15);

        $reportTypes = [
            'revenue' => 'Raport Venituri',
            'profitability' => 'Raport Profitabilitate',
            'contract_stats' => 'Statistici Contracte',
            'client_analysis' => 'Analiză Clienți',
        ];

        return view('theme::reports.index', compact('company', 'reports', 'reportTypes'));
    }

    /**
     * Show the form for generating a new report
     */
    public function create(Request $request)
    {
        $companyId = $request->input('company_id') ?? session('active_company_id');

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Vă rugăm să selectați o companie.');
        }

        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        $reportTypes = [
            'revenue' => [
                'name' => 'Raport Venituri',
                'description' => 'Analizează veniturile din facturi plătite, cu defalcare lunară.',
            ],
            'profitability' => [
                'name' => 'Raport Profitabilitate',
                'description' => 'Compară veniturile cu valoarea contractelor și analizează profitabilitatea.',
            ],
            'contract_stats' => [
                'name' => 'Statistici Contracte',
                'description' => 'Oferă statistici despre contracte: statusuri, timpul de semnare, expirări.',
            ],
            'client_analysis' => [
                'name' => 'Analiză Clienți',
                'description' => 'Analizează clienții după venituri generate și număr de contracte.',
            ],
        ];

        return view('theme::reports.create', compact('company', 'reportTypes'));
    }

    /**
     * Generate and store a new financial report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'report_type' => 'required|in:revenue,profitability,contract_stats,client_analysis',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'force_refresh' => 'nullable|boolean',
        ], [
            'company_id.required' => 'Compania este obligatorie.',
            'report_type.required' => 'Tipul de raport este obligatoriu.',
            'period_start.required' => 'Data de început este obligatorie.',
            'period_end.required' => 'Data de sfârșit este obligatorie.',
            'period_end.after' => 'Data de sfârșit trebuie să fie după data de început.',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $this->authorize('view', $company);

        try {
            $startDate = Carbon::parse($validated['period_start']);
            $endDate = Carbon::parse($validated['period_end']);
            $forceRefresh = $validated['force_refresh'] ?? false;

            $report = $this->reportService->getReport(
                $validated['company_id'],
                $validated['report_type'],
                $startDate,
                $endDate,
                $forceRefresh
            );

            \Log::info('Financial report generated', [
                'user_id' => auth()->id(),
                'report_id' => $report->id,
                'report_type' => $report->report_type,
            ]);

            return redirect()->route('reports.show', $report->id)
                ->with('success', 'Raportul a fost generat cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to generate financial report', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la generarea raportului: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified financial report
     */
    public function show(FinancialReport $report)
    {
        $company = $report->company;
        $this->authorize('view', $company);

        // Check if report is stale
        $isStale = $report->isStale();

        return view('theme::reports.show', compact('report', 'isStale'));
    }

    /**
     * Remove the specified financial report
     */
    public function destroy(FinancialReport $report)
    {
        $company = $report->company;
        $this->authorize('view', $company);

        try {
            $report->delete();

            return redirect()->route('reports.index')
                ->with('success', 'Raportul a fost șters cu succes!');

        } catch (\Exception $e) {
            \Log::error('Failed to delete financial report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea raportului.');
        }
    }

    /**
     * Refresh an existing report
     */
    public function refresh(FinancialReport $report)
    {
        $company = $report->company;
        $this->authorize('view', $company);

        try {
            $newReport = $this->reportService->getReport(
                $report->company_id,
                $report->report_type,
                $report->period_start,
                $report->period_end,
                true // Force refresh
            );

            // Delete old report
            $report->delete();

            return redirect()->route('reports.show', $newReport->id)
                ->with('success', 'Raportul a fost actualizat cu succes!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la actualizarea raportului.');
        }
    }

    /**
     * Download report as PDF
     */
    public function downloadPdf(FinancialReport $report)
    {
        $company = $report->company;
        $this->authorize('view', $company);

        try {
            // PDF generation will be implemented in Phase 8
            return redirect()->back()
                ->with('info', 'Descărcarea PDF va fi implementată în curând.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la generarea PDF-ului.');
        }
    }

    /**
     * Export report as Excel
     */
    public function exportExcel(FinancialReport $report)
    {
        $company = $report->company;
        $this->authorize('view', $company);

        try {
            // Excel export will be implemented later
            return redirect()->back()
                ->with('info', 'Exportul Excel va fi implementat în curând.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la exportul Excel.');
        }
    }

    /**
     * Generate revenue report (quick action)
     */
    public function revenueReport(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'period' => 'required|in:this_month,last_month,this_quarter,last_quarter,this_year,last_year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date|after:start_date',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $this->authorize('view', $company);

        // Calculate date range based on period
        switch ($validated['period']) {
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $startDate = now()->startOfQuarter();
                $endDate = now()->endOfQuarter();
                break;
            case 'last_quarter':
                $startDate = now()->subQuarter()->startOfQuarter();
                $endDate = now()->subQuarter()->endOfQuarter();
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'last_year':
                $startDate = now()->subYear()->startOfYear();
                $endDate = now()->subYear()->endOfYear();
                break;
            case 'custom':
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['end_date']);
                break;
        }

        try {
            $report = $this->reportService->getReport(
                $validated['company_id'],
                'revenue',
                $startDate,
                $endDate
            );

            return redirect()->route('reports.show', $report->id);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la generarea raportului.');
        }
    }

    /**
     * Clean up old reports (admin action)
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'days_old' => 'required|integer|min:7|max:365',
        ]);

        try {
            $deleted = $this->reportService->cleanupOldReports($validated['days_old']);

            return redirect()->back()
                ->with('success', "{$deleted} rapoarte vechi au fost șterse.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'A apărut o eroare la curățarea rapoartelor vechi.');
        }
    }
}
