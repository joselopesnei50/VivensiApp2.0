<?php

namespace App\Http\Controllers;

use App\Models\BillingAddon;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AddonService;
use App\Services\Billing\InvoiceService;
use App\Services\EmailQuotaService;
use App\Support\TeamAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minha conta → Adicionais: o administrador da conta contrata número extra
 * no bot e pacotes de e-mail, e escolhe quem da equipe usa o bot.
 * Liberação na hora; a cobrança entra na próxima fatura.
 */
class ClientAddonsController extends Controller
{
    public function __construct(private AddonService $addons)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->isAccountAdmin(), 403, 'Só o administrador da conta gerencia os adicionais.');
            return $next($request);
        });
    }

    public function index(EmailQuotaService $quota): View
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        return view('client.addons.index', [
            'tenant'      => $tenant,
            'catalog'     => $this->addons->catalog(),
            'quantities'  => $this->addons->quantities($tenant),
            'canContract' => $this->addons->canContract($tenant),
            'botLimit'    => $this->addons->botSeatLimit($tenant),
            'botUsed'     => $this->addons->botSeatsUsed($tenant),
            'members'     => $this->addons->botCandidates($tenant),
            'emailBase'   => $quota->getBaseQuota($tenant),
            'emailExtra'  => $quota->getExtraQuota($tenant),
            'isYearly'    => ($tenant->plan?->interval ?? 'monthly') === 'yearly',
            'included'    => AddonService::BOT_INCLUDED,
            'isFree'      => $this->addons->isFreeAccount($tenant),
            'billingText' => $this->addons->canContract($tenant) ? $this->nextBillingText($tenant) : null,
        ]);
    }

    /** POST /minha-conta/adicionais/{code} — define a quantidade (0 cancela). */
    public function update(Request $request, string $code): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $addon  = BillingAddon::where('code', $code)->firstOrFail();
        $data   = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:' . $addon->max_quantity],
        ]);

        $before = $this->addons->quantity($tenant, $addon->code);
        $error  = $this->addons->setQuantity($tenant, $addon, (int) $data['quantity'], auth()->user());
        if ($error) {
            return back()->with('error', $error);
        }

        $after = (int) $data['quantity'];
        $quando = $this->nextBillingText($tenant);
        $msg = match (true) {
            $after > $before => 'Pronto! O adicional já está liberado. ' . $quando,
            $after === 0     => 'Adicional cancelado. Ele não entra mais nas próximas faturas.',
            $after < $before => 'Quantidade reduzida. A próxima fatura já vem com o novo valor.',
            default          => 'Nada mudou.',
        };
        return back()->with('success', $msg);
    }

    /**
     * Em qual fatura o adicional entra. A fatura sai 5 dias antes do
     * vencimento: se a do período atual já foi gerada, o adicional vai para
     * a seguinte (não mexemos numa cobrança já emitida).
     */
    private function nextBillingText($tenant): string
    {
        try {
            $start = app(InvoiceService::class)->nextPeriodStart($tenant);
            $open  = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
                ->orderByDesc('period_start')->first();
            $txt = 'A cobrança entra na fatura do período que começa em ' . $start->format('d/m/Y') . '.';
            if ($open) {
                $txt = 'Sua fatura com vencimento em ' . $open->due_date->format('d/m/Y') . ' já foi gerada e não muda. ' . $txt;
            }
            return $txt;
        } catch (\Throwable $e) {
            return 'A cobrança entra na sua próxima fatura.';
        }
    }

    /** POST /minha-conta/adicionais/bot/{user} — liga/desliga o bot para alguém. */
    public function botAccess(Request $request, int $user): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $member = User::where('tenant_id', $tenant->id)->findOrFail($user);
        $on     = $request->boolean('on');

        $error = $this->addons->setBotAccess($tenant, $member, $on);
        if ($error) {
            return back()->with('error', $error);
        }
        return back()->with('success', $on
            ? "{$member->name} já pode usar o bot pelo WhatsApp."
            : "{$member->name} não usa mais o bot.");
    }

    private function isAccountAdmin(): bool
    {
        $u = auth()->user();
        return $u && $u->tenant_id
            && in_array($u->role, ['ngo', 'manager', 'common'], true)
            && !TeamAccess::appliesTo($u);
    }
}
