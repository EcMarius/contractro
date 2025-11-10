<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractSignature;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractSignatureController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function getByToken($token)
    {
        $signature = ContractSignature::where('verification_token', $token)
            ->with(['contract', 'user'])
            ->firstOrFail();

        if ($signature->isExpired()) {
            $signature->update(['status' => 'expired']);
            return response()->json(['success' => false, 'message' => 'This signature request has expired'], 410);
        }

        return response()->json(['success' => true, 'data' => $signature]);
    }

    public function sign($token, Request $request)
    {
        $signature = ContractSignature::where('verification_token', $token)->firstOrFail();

        if ($signature->status === 'signed') {
            return response()->json(['success' => false, 'message' => 'Already signed'], 422);
        }

        if ($signature->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Expired'], 410);
        }

        $validator = Validator::make($request->all(), [
            'signature_data' => 'required|string',
            'signature_type' => 'required|in:drawn,typed,uploaded',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $this->contractService->signContract($signature, $request->signature_data, $request->signature_type);
            return response()->json(['success' => true, 'message' => 'Signed successfully', 'data' => $signature->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function decline($token, Request $request)
    {
        $signature = ContractSignature::where('verification_token', $token)->firstOrFail();
        $signature->decline($request->reason);

        return response()->json(['success' => true, 'message' => 'Declined']);
    }

    public function resend($id)
    {
        $signature = ContractSignature::findOrFail($id);

        if (!$signature->contract->canBeEditedBy(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $signature->update(['expires_at' => now()->addDays(14), 'requested_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Resent']);
    }

    public function cancel($id)
    {
        $signature = ContractSignature::findOrFail($id);

        if (!$signature->contract->canBeEditedBy(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $signature->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Cancelled']);
    }
}
