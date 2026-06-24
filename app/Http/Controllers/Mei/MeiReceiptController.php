<?php

namespace App\Http\Controllers\Mei;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vivensi — Módulo MEI / Recibos.
 *
 * Espelha o ReceiptController do NGO, mas:
 *  - vincula a Client (do CRM MEI) em vez de NgoDonor;
 *  - description default = "Recibo de prestação de serviço/venda";
 *  - mesma infra de link público assinado (public_receipt_token + bidx +
 *    expires_at) já existente no Transaction model — não é dupplicado.
 *
 * Toda Transaction income criada aqui:
 *  - tem token UUID gerado pelo Transaction::booted() (automaticamente);
 *  - tem receipt_auth_code (16 hex) gerado pelo Transaction::booted();
 *  - conta no Termômetro do Teto MEI (lê income/paid do ano).
 */
class MeiReceiptController extends Controller
{
    private function publicLinkTtlDays(): ?int
    {
        $ttl = config('receipts.public_link_ttl_days', 30);
        return $ttl === null ? null : (int) $ttl;
    }

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $receipts = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->whereNotNull('public_receipt_token_bidx')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        $receipts->getCollection()->each(function (Transaction $t) {
            // Garante token caso alguma Transaction antiga não tenha (sanity).
            if (!$t->public_receipt_token) {
                $t->public_receipt_token = (string) Str::uuid();
                if ($this->publicLinkTtlDays() !== null) {
                    $t->public_receipt_expires_at = now()->addDays((int) $this->publicLinkTtlDays());
                }
                $t->save();
            }
        });

        return view('mei.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'document', 'email']);

        return view('mei.receipts.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'recipient_name'     => 'nullable|string|max:180',
            'recipient_document' => 'nullable|string|max:25',
            'description'        => 'nullable|string|max:255',
            'amount'             => 'required',
            'date'               => 'required|date',
        ]);

        $amount = function_exists('sanitize_br_currency')
            ? sanitize_br_currency($request->input('amount'))
            : (float) str_replace(',', '.', (string) $request->input('amount'));

        $transaction = new Transaction();
        $transaction->tenant_id    = $tenantId;
        // Token/auth_code são gerados automaticamente pelo Transaction::booted()
        // quando type=income, mas regeramos aqui pra garantir TTL atualizado.
        $transaction->public_receipt_token = (string) Str::uuid();
        $ttlDays = $this->publicLinkTtlDays();
        $transaction->public_receipt_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);

        $clientId = $validated['client_id'] ?? null;
        $client = null;
        if ($clientId) {
            $client = Client::where('tenant_id', $tenantId)->findOrFail($clientId);
            $transaction->client_id   = $client->id;
            $transaction->description = $validated['description']
                ?? sprintf('Recibo — %s', $client->name);
        } else {
            // Recibo avulso (cliente não cadastrado). description guarda
            // identificação para auditoria — nome+doc se informados.
            $recipient = trim((string) ($validated['recipient_name'] ?? ''));
            $doc       = trim((string) ($validated['recipient_document'] ?? ''));
            $base      = $recipient !== ''
                ? sprintf('Recibo — %s%s', $recipient, $doc !== '' ? " ({$doc})" : '')
                : 'Recibo de prestação de serviço/venda';
            $transaction->description = $validated['description'] ?? $base;
        }

        $transaction->amount   = $amount;
        $transaction->type     = 'income';
        $transaction->date     = $validated['date'];
        $transaction->status   = 'paid';
        $transaction->save();

        return redirect('/personal/receipts')
            ->with('success', 'Recibo gerado com sucesso! Link público pronto pra enviar ao cliente.');
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

        return back()->with('success', 'Link público do recibo regenerado. O anterior foi revogado.');
    }

    public function revokeLink($id)
    {
        $transaction = Transaction::where('tenant_id', auth()->user()->tenant_id)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        if (!$transaction->public_receipt_token) {
            $transaction->public_receipt_token = (string) Str::uuid();
        }
        $transaction->public_receipt_expires_at = now()->subSecond();
        $transaction->save();

        return back()->with('success', 'Link público revogado.');
    }

    // ── NFS-e (opção C: anexa nota emitida no portal nfse.gov.br) ────────────

    private const NFSE_DISK = 'local';
    private const NFSE_MAX_KB = 5120; // 5 MB

    /**
     * Anexa NFS-e (número + data + PDF) à Transaction. O MEI emite a nota no
     * Emissor Nacional gratuito do governo e cola aqui — Vivensi vira o
     * "guarda-tudo" do dossiê fiscal sem custo recorrente.
     */
    public function attachNfse(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $transaction = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'nfse_numero'     => 'required|string|max:60',
            'nfse_emitida_em' => 'required|date',
            'pdf'             => ['nullable', 'file', 'mimes:pdf', 'max:' . self::NFSE_MAX_KB],
        ]);

        // Se uma NFS-e anterior existir, apaga o PDF antigo antes de substituir.
        if ($request->hasFile('pdf') && $transaction->nfse_url_pdf) {
            Storage::disk(self::NFSE_DISK)->delete($transaction->nfse_url_pdf);
        }

        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            $filename = sprintf(
                'nfse_%d_%s.pdf',
                $transaction->id,
                Str::random(10)
            );
            $path = $file->storeAs(
                sprintf('private/nfse/%d', $tenantId),
                $filename,
                self::NFSE_DISK
            );
            $transaction->nfse_url_pdf = $path;
        }

        $transaction->nfse_numero     = $validated['nfse_numero'];
        $transaction->nfse_emitida_em = $validated['nfse_emitida_em'];
        $transaction->save();

        return back()->with('success', 'NFS-e anexada com sucesso. Receita agora tem dossiê fiscal completo. 🎯');
    }

    /**
     * Remove NFS-e da Transaction (limpa campos + apaga arquivo).
     */
    public function detachNfse($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $transaction = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        if ($transaction->nfse_url_pdf) {
            Storage::disk(self::NFSE_DISK)->delete($transaction->nfse_url_pdf);
        }
        $transaction->nfse_numero     = null;
        $transaction->nfse_url_pdf    = null;
        $transaction->nfse_emitida_em = null;
        $transaction->save();

        return back()->with('success', 'NFS-e removida.');
    }

    /**
     * Download privado do PDF da NFS-e.
     */
    public function downloadNfse($id): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $transaction = Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->where('id', $id)
            ->firstOrFail();

        abort_unless(
            $transaction->nfse_url_pdf && Storage::disk(self::NFSE_DISK)->exists($transaction->nfse_url_pdf),
            404,
            'NFS-e não encontrada.'
        );

        $filename = sprintf('NFS-e %s.pdf', $transaction->nfse_numero ?: $transaction->id);
        return Storage::disk(self::NFSE_DISK)->download($transaction->nfse_url_pdf, $filename);
    }
}
