<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractSignature;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractSigningController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        // No auth middleware - this is a public signing page
        $this->signatureService = $signatureService;
    }

    /**
     * Show the signing page for a contract party
     *
     * URL format: /sign/{partyId}/{token}
     */
    public function show(int $partyId, string $token)
    {
        try {
            $party = ContractParty::with(['contract.contractType', 'contract.company'])
                ->findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return view('theme::contracts.signing.error', [
                    'error' => 'Link de semnare invalid sau expirat. Vă rugăm contactați emitentul contractului.',
                ]);
            }

            $contract = $party->contract;

            // Check if contract is in pending status
            if (!in_array($contract->status, ['pending', 'signed'])) {
                return view('theme::contracts.signing.error', [
                    'error' => 'Acest contract nu mai este disponibil pentru semnare.',
                ]);
            }

            // Check if party already signed
            if ($party->hasSigned()) {
                return view('theme::contracts.signing.already-signed', [
                    'party' => $party,
                    'contract' => $contract,
                    'signature' => $party->signatures()->where('code_verified', true)->first(),
                ]);
            }

            return view('theme::contracts.signing.show', [
                'party' => $party,
                'contract' => $contract,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading signing page', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return view('theme::contracts.signing.error', [
                'error' => 'A apărut o eroare la încărcarea paginii de semnare.',
            ]);
        }
    }

    /**
     * Initiate SMS verification for signing
     */
    public function initiateSms(Request $request, int $partyId, string $token)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:50',
        ], [
            'phone.required' => 'Numărul de telefon este obligatoriu.',
        ]);

        try {
            $party = ContractParty::findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link de semnare invalid.',
                ], 403);
            }

            // Check if party already signed
            if ($party->hasSigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ați semnat deja acest contract.',
                ], 400);
            }

            // Check for rate limiting (max 3 SMS per 30 minutes)
            $recentAttempts = ContractSignature::where('party_id', $partyId)
                ->where('code_sent_at', '>', now()->subMinutes(30))
                ->count();

            if ($recentAttempts >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ați depășit numărul maxim de încercări. Vă rugăm așteptați 30 de minute.',
                ], 429);
            }

            // Initiate SMS signing
            $signature = $this->signatureService->initiateSigning($party, $validated['phone']);

            Log::info('SMS verification initiated', [
                'party_id' => $partyId,
                'phone' => $validated['phone'],
                'signature_id' => $signature->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Codul de verificare a fost trimis pe telefon.',
                'signature_id' => $signature->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initiate SMS signing', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la trimiterea codului SMS.',
            ], 500);
        }
    }

    /**
     * Verify SMS code and complete signing
     */
    public function verifySms(Request $request, int $partyId, string $token)
    {
        $validated = $request->validate([
            'signature_id' => 'required|exists:contract_signatures,id',
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Codul de verificare este obligatoriu.',
            'code.size' => 'Codul trebuie să conțină exact 6 cifre.',
        ]);

        try {
            $party = ContractParty::findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link de semnare invalid.',
                ], 403);
            }

            $signature = ContractSignature::findOrFail($validated['signature_id']);

            // Verify signature belongs to this party
            if ($signature->party_id !== $partyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semnătura nu aparține acestei părți.',
                ], 403);
            }

            // Verify code
            if (!$signature->verifyCode($validated['code'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cod de verificare invalid sau expirat.',
                ], 400);
            }

            // Complete signing
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            $completed = $this->signatureService->completeSigning($signature, $ipAddress, $userAgent);

            if (!$completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'A apărut o eroare la finalizarea semnării.',
                ], 500);
            }

            Log::info('Contract signed successfully', [
                'party_id' => $partyId,
                'signature_id' => $signature->id,
                'contract_id' => $party->contract_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contractul a fost semnat cu succes!',
                'redirect_url' => route('signing.success', ['partyId' => $partyId, 'token' => $token]),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify SMS and complete signing', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la verificarea codului.',
            ], 500);
        }
    }

    /**
     * Resend SMS verification code
     */
    public function resendSms(Request $request, int $partyId, string $token)
    {
        $validated = $request->validate([
            'signature_id' => 'required|exists:contract_signatures,id',
        ]);

        try {
            $party = ContractParty::findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link de semnare invalid.',
                ], 403);
            }

            $signature = ContractSignature::findOrFail($validated['signature_id']);

            // Check rate limiting
            if ($signature->code_sent_at && now()->diffInSeconds($signature->code_sent_at) < 60) {
                $waitTime = 60 - now()->diffInSeconds($signature->code_sent_at);
                return response()->json([
                    'success' => false,
                    'message' => "Vă rugăm așteptați {$waitTime} secunde înainte de a retrimite codul.",
                ], 429);
            }

            // Resend code
            $signature->sendVerificationCode($signature->verification_phone);

            return response()->json([
                'success' => true,
                'message' => 'Codul a fost retrimis.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend SMS code', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la retrimiterea codului.',
            ], 500);
        }
    }

    /**
     * Show success page after signing
     */
    public function success(int $partyId, string $token)
    {
        try {
            $party = ContractParty::with(['contract.company', 'signatures'])
                ->findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return view('theme::contracts.signing.error', [
                    'error' => 'Link de semnare invalid.',
                ]);
            }

            // Verify party has signed
            if (!$party->hasSigned()) {
                return redirect()->route('signing.show', ['partyId' => $partyId, 'token' => $token])
                    ->with('error', 'Contractul nu a fost încă semnat.');
            }

            $signature = $party->signatures()->where('code_verified', true)->first();

            return view('theme::contracts.signing.success', [
                'party' => $party,
                'contract' => $party->contract,
                'signature' => $signature,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading success page', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return view('theme::contracts.signing.error', [
                'error' => 'A apărut o eroare.',
            ]);
        }
    }

    /**
     * Download signed contract PDF (public)
     */
    public function downloadPdf(int $partyId, string $token)
    {
        try {
            $party = ContractParty::with('contract')->findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                abort(403, 'Link de semnare invalid.');
            }

            // Verify party has signed
            if (!$party->hasSigned()) {
                abort(403, 'Trebuie să semnați contractul înainte de a-l descărca.');
            }

            // PDF generation will be implemented in Phase 8
            return redirect()->back()
                ->with('info', 'Descărcarea PDF va fi implementată în curând.');

        } catch (\Exception $e) {
            Log::error('Error downloading contract PDF', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'A apărut o eroare la descărcarea contractului.');
        }
    }

    /**
     * Handwritten signature upload
     */
    public function uploadHandwritten(Request $request, int $partyId, string $token)
    {
        $validated = $request->validate([
            'signature_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'signature_image.required' => 'Imaginea cu semnătura este obligatorie.',
            'signature_image.image' => 'Fișierul trebuie să fie o imagine.',
            'signature_image.max' => 'Imaginea nu poate depăși 2MB.',
        ]);

        try {
            $party = ContractParty::findOrFail($partyId);

            // Verify signing token
            if ($party->signing_token !== $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link de semnare invalid.',
                ], 403);
            }

            // Upload handwritten signature
            $path = $request->file('signature_image')->store('signatures/handwritten', 'public');

            $signature = ContractSignature::create([
                'contract_id' => $party->contract_id,
                'party_id' => $party->id,
                'signature_method' => 'handwritten',
                'signature_data' => $path,
                'signed_at' => now(),
                'ip_address' => $request->ip(),
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Complete signing
            $this->signatureService->completeSigning($signature, $request->ip(), $request->userAgent());

            return response()->json([
                'success' => true,
                'message' => 'Semnătura a fost încărcată cu succes!',
                'redirect_url' => route('signing.success', ['partyId' => $partyId, 'token' => $token]),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upload handwritten signature', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la încărcarea semnăturii.',
            ], 500);
        }
    }
}
