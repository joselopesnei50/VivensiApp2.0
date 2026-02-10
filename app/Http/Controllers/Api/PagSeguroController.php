<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Tenant;
use App\Services\PagSeguroService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PagSeguroController extends Controller
{
    protected $pagSeguroService;

    public function __construct(PagSeguroService $pagSeguroService)
    {
        $this->pagSeguroService = $pagSeguroService;
    }

    /**
     * Iniatiate a Checkout for a Tenant/Plan.
     * Request: { amount, description, sender: { name, email, cpf, phone }, tenant_id }
     */
    public function checkout(Request $request)
    {
        // 1. Validate
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'sender.name' => 'required|string',
            'sender.email' => 'required|email'. (app()->environment('production') ? '' : ''), // Allow sandbox emails
             // CPF is critical for PagSeguro
            'sender.cpf' => 'required|string', 
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        // 2. Create Pending Transaction
        $tenant = Tenant::findOrFail($request->tenant_id);
        
        // Generate a unique reference for PagSeguro to send back in webhook
        // Format: VIVENSI_TENANTID_TIMESTAMP
        $reference = 'VIVENSI_' . $tenant->id . '_' . time();

        $transaction = Transaction::create([
            'tenant_id' => $tenant->id,
            'amount' => $request->amount,
            'description' => $request->description,
            'type' => 'income',
            'status' => 'pending', // Pending payment
            'date' => now(),
            'external_id' => $reference, // Temporarily store ref here until we get code
            // Add other mandatory fields for Vivensi system (like category if needed)
        ]);

        // 3. Call PagSeguro
        $paymentData = [
            'reference' => $reference,
            'amount' => $request->amount,
            'description' => $request->description,
            'sender' => $request->sender,
        ];

        $result = $this->pagSeguroService->createPayment($paymentData);

        if ($result && isset($result['code'])) {
            // Update transaction with actual PagSeguro Code
            $transaction->update(['external_id' => $result['code']]);

            return response()->json([
                'status' => 'success',
                'payment_url' => $result['paymentLink'],
                'code' => $result['code']
            ]);
        }

        // Fail
        $transaction->update(['status' => 'failed']);
        return response()->json(['status' => 'error', 'message' => 'Could not initiate payment with PagSeguro.'], 500);
    }
}
