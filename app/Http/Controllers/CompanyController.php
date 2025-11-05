<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of user's companies
     */
    public function index()
    {
        $user = auth()->user();
        $companies = Company::where('user_id', $user->id)
            ->with(['contracts', 'invoices'])
            ->withCount(['contracts', 'invoices'])
            ->latest()
            ->paginate(10);

        return view('theme::companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company
     */
    public function create()
    {
        return view('theme::companies.create');
    }

    /**
     * Store a newly created company
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'cui' => 'nullable|string|max:50',
            'reg_com' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'name.required' => 'Numele companiei este obligatoriu.',
            'name.min' => 'Numele trebuie să aibă cel puțin 2 caractere.',
            'cui.max' => 'CUI-ul poate avea maximum 50 caractere.',
            'email.email' => 'Email-ul trebuie să fie valid.',
            'website.url' => 'Website-ul trebuie să fie o adresă URL validă.',
            'logo.image' => 'Logo-ul trebuie să fie o imagine.',
            'logo.mimes' => 'Logo-ul trebuie să fie în format jpeg, png, jpg sau svg.',
            'logo.max' => 'Logo-ul nu poate depăși 2MB.',
        ]);

        // Validate CUI if provided
        if (!empty($validated['cui'])) {
            if (!Company::validateCUI($validated['cui'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['cui' => 'CUI-ul introdus nu este valid. Vă rugăm verificați.']);
            }
        }

        try {
            DB::beginTransaction();

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('company-logos', 'public');
                $validated['logo_path'] = $logoPath;
            }

            $company = Company::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'cui' => $validated['cui'] ?? null,
                'reg_com' => $validated['reg_com'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'county' => $validated['county'] ?? null,
                'country' => $validated['country'] ?? 'România',
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'iban' => $validated['iban'] ?? null,
                'logo_path' => $validated['logo_path'] ?? null,
                'settings' => [
                    'invoice_settings' => [
                        'default_series' => 'FACT',
                        'vat_rate' => 19,
                        'payment_terms' => 30,
                    ],
                ],
            ]);

            DB::commit();

            \Log::info('Company created successfully', [
                'user_id' => auth()->id(),
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            return redirect()->route('companies.show', $company->id)
                ->with('success', 'Compania a fost creată cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create company', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la crearea companiei. Vă rugăm încercați din nou.');
        }
    }

    /**
     * Display the specified company
     */
    public function show(Company $company)
    {
        $this->authorize('view', $company);

        $company->load(['contracts', 'invoices', 'contractTypes']);

        // Get financial stats
        $stats = [
            'total_contracts' => $company->contracts()->count(),
            'active_contracts' => $company->contracts()->where('status', 'active')->count(),
            'total_invoices' => $company->invoices()->count(),
            'paid_invoices' => $company->invoices()->where('status', 'paid')->count(),
            'total_revenue' => $company->invoices()->where('status', 'paid')->sum('total_amount'),
            'pending_revenue' => $company->invoices()->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
            'overdue_invoices' => $company->invoices()->where('status', 'overdue')->count(),
        ];

        // Recent contracts
        $recentContracts = $company->contracts()
            ->with('contractType')
            ->latest()
            ->limit(5)
            ->get();

        // Recent invoices
        $recentInvoices = $company->invoices()
            ->latest('issue_date')
            ->limit(5)
            ->get();

        return view('theme::companies.show', compact('company', 'stats', 'recentContracts', 'recentInvoices'));
    }

    /**
     * Show the form for editing the specified company
     */
    public function edit(Company $company)
    {
        $this->authorize('update', $company);

        return view('theme::companies.edit', compact('company'));
    }

    /**
     * Update the specified company
     */
    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'cui' => 'nullable|string|max:50',
            'reg_com' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'default_series' => 'nullable|string|max:10',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|integer|min:0|max:365',
        ], [
            'name.required' => 'Numele companiei este obligatoriu.',
            'email.email' => 'Email-ul trebuie să fie valid.',
            'website.url' => 'Website-ul trebuie să fie o adresă URL validă.',
            'logo.image' => 'Logo-ul trebuie să fie o imagine.',
            'vat_rate.numeric' => 'Cota TVA trebuie să fie un număr.',
            'vat_rate.min' => 'Cota TVA trebuie să fie minimum 0.',
            'vat_rate.max' => 'Cota TVA trebuie să fie maximum 100.',
        ]);

        // Validate CUI if provided and changed
        if (!empty($validated['cui']) && $validated['cui'] !== $company->cui) {
            if (!Company::validateCUI($validated['cui'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['cui' => 'CUI-ul introdus nu este valid.']);
            }
        }

        try {
            DB::beginTransaction();

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }

                $logoPath = $request->file('logo')->store('company-logos', 'public');
                $validated['logo_path'] = $logoPath;
            }

            // Update company data
            $company->update([
                'name' => $validated['name'],
                'cui' => $validated['cui'] ?? null,
                'reg_com' => $validated['reg_com'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'county' => $validated['county'] ?? null,
                'country' => $validated['country'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'iban' => $validated['iban'] ?? null,
                'logo_path' => $validated['logo_path'] ?? $company->logo_path,
            ]);

            // Update invoice settings if provided
            if ($request->has(['default_series', 'vat_rate', 'payment_terms'])) {
                $settings = $company->settings ?? [];
                $settings['invoice_settings'] = [
                    'default_series' => $validated['default_series'] ?? 'FACT',
                    'vat_rate' => $validated['vat_rate'] ?? 19,
                    'payment_terms' => $validated['payment_terms'] ?? 30,
                ];
                $company->update(['settings' => $settings]);
            }

            DB::commit();

            \Log::info('Company updated successfully', [
                'user_id' => auth()->id(),
                'company_id' => $company->id,
            ]);

            return redirect()->route('companies.show', $company->id)
                ->with('success', 'Compania a fost actualizată cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update company', [
                'user_id' => auth()->id(),
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'A apărut o eroare la actualizarea companiei.');
        }
    }

    /**
     * Remove the specified company
     */
    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        try {
            DB::beginTransaction();

            // Check if company has contracts or invoices
            if ($company->contracts()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Nu puteți șterge o companie care are contracte asociate.');
            }

            if ($company->invoices()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Nu puteți șterge o companie care are facturi asociate.');
            }

            // Delete logo
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $companyName = $company->name;
            $company->delete();

            DB::commit();

            \Log::info('Company deleted successfully', [
                'user_id' => auth()->id(),
                'company_name' => $companyName,
            ]);

            return redirect()->route('companies.index')
                ->with('success', 'Compania a fost ștearsă cu succes!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to delete company', [
                'user_id' => auth()->id(),
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'A apărut o eroare la ștergerea companiei.');
        }
    }

    /**
     * Switch active company for user
     */
    public function switch(Company $company)
    {
        $this->authorize('view', $company);

        session(['active_company_id' => $company->id]);

        return redirect()->back()
            ->with('success', 'Compania activă a fost schimbată în ' . $company->name);
    }

    /**
     * Validate CUI via AJAX
     */
    public function validateCui(Request $request)
    {
        $cui = $request->input('cui');

        if (empty($cui)) {
            return response()->json(['valid' => false, 'message' => 'CUI-ul este obligatoriu']);
        }

        $isValid = Company::validateCUI($cui);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'CUI valid' : 'CUI invalid. Vă rugăm verificați cifra de control.',
        ]);
    }
}
