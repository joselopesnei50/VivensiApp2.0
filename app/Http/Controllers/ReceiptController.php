<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    private function publicLinkTtlDays(): ?int
    {
        $ttl = config('receipts.public_link_ttl_days', 30);
        return $ttl === null ? null : (int) $ttl;
    }

    public function index()
    {
        // Listar apenas entradas (doações) para emissão de recibo
        $donations = Transaction::where('tenant_id', auth()->user()->tenant_id)
                                ->where('type', 'income')
                                ->orderBy('date', 'desc')
                                ->paginate(10);

        // Ensure public token exists for previously created receipts/transactions.
        // (Preferably handled by a backfill job, but this keeps links working after deploy.)
        $donations->getCollection()->each(function (Transaction $t) {
            if (!$t->public_receipt_token) {
                $t->public_receipt_token = (string) Str::uuid();
                $t->saveQuietly();
            }

            if (!$t->public_receipt_expires_at) {
                $ttlDays = $this->publicLinkTtlDays();
                $t->public_receipt_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);
                $t->saveQuietly();
            }

            if (!$t->receipt_auth_code) {
                $code = strtoupper(bin2hex(random_bytes(8)));
                for ($i = 0; $i < 5; $i++) {
                    $exists = Transaction::withoutGlobalScopes()
                        ->where('receipt_auth_code', $code)
                        ->exists();
                    if (!$exists) {
                        break;
                    }
                    $code = strtoupper(bin2hex(random_bytes(8)));
                }
                $t->receipt_auth_code = $code;
                $t->saveQuietly();
            }
        });
                                
        return view('ngo.receipts.index', compact('donations'));
    }

    public function create()
    {
        // Buscar doadores cadastrados para o autocomplete
        $donors = \App\Models\NgoDonor::where('tenant_id', auth()->user()->tenant_id)
                                      ->orderBy('name')
                                      ->get();
                                      
        return view('ngo.receipts.create', compact('donors'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'ngo_donor_id' => [
                'nullable',
                'integer',
                Rule::exists('ngo_donors', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'description' => 'nullable|string|max:255',
            'amount' => 'required',
            'date' => 'required|date',
        ]);

        $data = $request->all();
        
        // Sanitização (R$ 1.000,00 -> 1000.00)
        if (isset($data['amount'])) {
             $data['amount'] = str_replace('.', '', $data['amount']);
             $data['amount'] = str_replace(',', '.', $data['amount']);
        }

        $transaction = new Transaction();
        $transaction->tenant_id = $tenantId;
        $transaction->public_receipt_token = (string) Str::uuid();
        $ttlDays = $this->publicLinkTtlDays();
        $transaction->public_receipt_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);

        $donorId = $validated['ngo_donor_id'] ?? null;
        $donor = null;
        if ($donorId) {
            $donor = \App\Models\NgoDonor::where('tenant_id', $tenantId)->findOrFail($donorId);
            $transaction->ngo_donor_id = $donor->id;
            $transaction->description = $donor->name;
        } else {
            $transaction->description = $validated['description'] ?? 'Doação';
        }

        $transaction->amount = $data['amount'];
        $transaction->type = 'income';
        $transaction->date = $validated['date'];
        $transaction->category_id = null; // Doação
        $transaction->status = 'paid';
        $transaction->save();

        if ($donor) {
            // Keep donor stats in sync.
            $donor->increment('total_donated', (float) $transaction->amount);
        }

        return redirect('/ngo/receipts')->with('success', 'Recibo gerado com sucesso! Agora você pode enviar.');
    }

    public function regenerateLink($id)
    {
        $transaction = Transaction::where('tenant_id', auth()->user()->tenant_id)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        $transaction->public_receipt_token = (string) Str::uuid();
        $ttlDays = $this->publicLinkTtlDays();
        $transaction->public_receipt_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);
        $transaction->save();

        return back()->with('success', 'Link público do recibo regenerado. O link anterior foi revogado.');
    }

    public function revokeLink($id)
    {
        $transaction = Transaction::where('tenant_id', auth()->user()->tenant_id)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        // Keep the token (so the public link shows "expired" instead of 404),
        // but expire it immediately.
        if (!$transaction->public_receipt_token) {
            $transaction->public_receipt_token = (string) Str::uuid();
        }
        $transaction->public_receipt_expires_at = now()->subSecond();
        $transaction->save();

        return back()->with('success', 'Link público do recibo revogado com sucesso.');
    }

    // Exibe o recibo publicamente
    public function show($token)
    {
        // Public route: do not apply tenant scopes (even if someone is logged in).
        $transaction = Transaction::withoutGlobalScopes()->where('public_receipt_token', $token)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->firstOrFail();

        if ($transaction->public_receipt_expires_at && $transaction->public_receipt_expires_at->isPast()) {
            return response()->view('public.receipt_expired', [], 410);
        }

        $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('id', $transaction->tenant_id)->first();

        return view('public.receipt', compact('transaction', 'tenant'));
    }

    public function validateForm()
    {
        return view('public.receipt_validate');
    }

    public function validateSubmit(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $input = trim((string) $request->input('query'));
        $token = null;
        $code = null;

        // Accept either full URL containing /r/{token} or the raw UUID token.
        if (preg_match('~\\/r\\/([0-9a-fA-F-]{36})~', $input, $m)) {
            $token = $m[1];
        } elseif (preg_match('~^[0-9a-fA-F-]{36}$~', $input)) {
            $token = $input;
        } elseif (preg_match('~^[0-9a-fA-F]{16}$~', $input)) {
            $code = strtoupper($input);
        }

        $result = [
            'status' => 'invalid', // valid|expired|invalid|error
            'message' => 'Não foi possível validar. Informe um link de recibo ou um código de validação (16 caracteres).',
            'transaction' => null,
            'tenant' => null,
            'receipt_url' => null,
        ];

        if (!$token && !$code) {
            return view('public.receipt_validate', compact('result'));
        }

        try {
            // Public validation: ignore tenant scopes to validate across organizations.
            $q = Transaction::withoutGlobalScopes()->where('type', 'income')->where('status', 'paid');
            $transaction = $token
                ? $q->where('public_receipt_token', $token)->first()
                : $q->where('receipt_auth_code', $code)->first();

            if (!$transaction) {
                $result['status'] = 'invalid';
                $result['message'] = 'Recibo não encontrado.';
                return view('public.receipt_validate', compact('result'));
            }

            if ($transaction->public_receipt_expires_at && $transaction->public_receipt_expires_at->isPast()) {
                $result['status'] = 'expired';
                $result['message'] = 'Recibo encontrado, mas o link público expirou.';
            } else {
                $result['status'] = 'valid';
                $result['message'] = 'Recibo válido.';
                if ($transaction->public_receipt_token) {
                    $result['receipt_url'] = url('/r/' . $transaction->public_receipt_token);
                }
            }

            $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('id', $transaction->tenant_id)->first();

            $result['transaction'] = $transaction;
            $result['tenant'] = $tenant;

            return view('public.receipt_validate', compact('result'));
        } catch (QueryException $e) {
            // Friendly error for missing migrations/columns in dev.
            $result['status'] = 'error';
            $result['message'] = 'Não foi possível validar no momento. Verifique se as migrations foram executadas.';
            return view('public.receipt_validate', compact('result'));
        }
    }
}
