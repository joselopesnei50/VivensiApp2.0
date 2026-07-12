<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserLgpdDataJob;
use App\Models\LgpdDataRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Self-service LGPD para o titular dos dados (usuario autenticado).
 *
 * Endpoints:
 *   GET  /eu/dados                          → dashboard com opcoes
 *   POST /eu/dados/exportar                 → solicita exportacao (dispara job)
 *   GET  /eu/dados/download/{token}         → download do ZIP (token 48h)
 *   POST /eu/dados/excluir                  → agenda deletion (grace 30d)
 *   POST /eu/dados/excluir/cancelar/{id}    → cancela dentro do grace
 *
 * Todos autenticados. Rate limited por Route middleware.
 */
class LgpdSelfServiceController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $exports = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->where('type', LgpdDataRequest::TYPE_EXPORT)
            ->latest()
            ->limit(10)
            ->get();

        $activeDeletion = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->where('type', LgpdDataRequest::TYPE_DELETE)
            ->where('status', LgpdDataRequest::STATUS_PENDING)
            ->whereNull('cancelled_at')
            ->latest()
            ->first();

        return view('lgpd.self-service', [
            'user'           => $user,
            'exports'        => $exports,
            'activeDeletion' => $activeDeletion,
        ]);
    }

    public function requestExport(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Rate limit: 1 exportacao a cada 6h por usuario
        $recent = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->where('type', LgpdDataRequest::TYPE_EXPORT)
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();

        if ($recent) {
            return back()->with('warning', 'Voce ja solicitou uma exportacao nas ultimas 6 horas. Aguarde para tentar novamente.');
        }

        $lgpdRequest = LgpdDataRequest::create([
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'type'       => LgpdDataRequest::TYPE_EXPORT,
            'status'     => LgpdDataRequest::STATUS_PENDING,
            'ip_address' => $request->ip(),
        ]);

        Log::info('LGPD_EXPORT_REQUESTED', [
            'request_id' => $lgpdRequest->id,
            'user_id'    => $user->id,
            'ip'         => $request->ip(),
        ]);

        ExportUserLgpdDataJob::dispatch($lgpdRequest->id);

        return back()->with('success', 'Exportacao solicitada. Voce recebera um email com o link de download em alguns minutos.');
    }

    /**
     * Download do ZIP via token opaco. NAO usa autenticacao — o token e a
     * autorizacao (48h de TTL). Isso permite abrir do email em qualquer sessao.
     */
    public function download(string $token): BinaryFileResponse|RedirectResponse
    {
        // Rate limit ja aplicado por middleware throttle na rota.
        $lgpdRequest = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('export_token', $token)
            ->where('type', LgpdDataRequest::TYPE_EXPORT)
            ->first();

        if (!$lgpdRequest) {
            abort(404, 'Link invalido ou ja utilizado.');
        }

        if (!$lgpdRequest->isTokenValid()) {
            abort(410, 'Este link expirou. Solicite uma nova exportacao em /eu/dados.');
        }

        if (!Storage::disk('local')->exists($lgpdRequest->export_file_path)) {
            Log::error('LGPD_EXPORT_FILE_MISSING', [
                'request_id' => $lgpdRequest->id,
                'path'       => $lgpdRequest->export_file_path,
            ]);
            abort(404, 'Arquivo indisponivel. Solicite nova exportacao.');
        }

        $lgpdRequest->increment('export_download_count');

        Log::info('LGPD_EXPORT_DOWNLOADED', [
            'request_id'    => $lgpdRequest->id,
            'user_id'       => $lgpdRequest->user_id,
            'download_num'  => $lgpdRequest->export_download_count,
        ]);

        $absolutePath = Storage::disk('local')->path($lgpdRequest->export_file_path);
        $filename     = basename($lgpdRequest->export_file_path);

        return response()->download($absolutePath, $filename, [
            'Content-Type'        => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function requestDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Voce precisa confirmar que entende as consequencias antes de solicitar a exclusao.',
        ]);

        $user = auth()->user();

        // Ja tem uma exclusao ativa? Nao duplica.
        $existing = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->where('type', LgpdDataRequest::TYPE_DELETE)
            ->where('status', LgpdDataRequest::STATUS_PENDING)
            ->whereNull('cancelled_at')
            ->exists();

        if ($existing) {
            return back()->with('warning', 'Voce ja tem uma solicitacao de exclusao em andamento.');
        }

        $lgpdRequest = LgpdDataRequest::create([
            'user_id'       => $user->id,
            'tenant_id'     => $user->tenant_id,
            'type'          => LgpdDataRequest::TYPE_DELETE,
            'status'        => LgpdDataRequest::STATUS_PENDING,
            'ip_address'    => $request->ip(),
            'scheduled_for' => now()->addDays(LgpdDataRequest::DELETION_GRACE_DAYS),
        ]);

        Log::warning('LGPD_DELETION_REQUESTED', [
            'request_id'    => $lgpdRequest->id,
            'user_id'       => $user->id,
            'scheduled_for' => $lgpdRequest->scheduled_for->toIso8601String(),
            'ip'            => $request->ip(),
        ]);

        return back()->with('success', 'Exclusao agendada para ' . $lgpdRequest->scheduled_for->format('d/m/Y') . '. Voce pode cancelar a qualquer momento antes dessa data em /eu/dados.');
    }

    public function cancelDelete(int $id): RedirectResponse
    {
        $user = auth()->user();

        $lgpdRequest = LgpdDataRequest::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('type', LgpdDataRequest::TYPE_DELETE)
            ->first();

        if (!$lgpdRequest) {
            abort(404, 'Solicitacao nao encontrada.');
        }

        if (!$lgpdRequest->isCancellable()) {
            return back()->with('warning', 'Esta exclusao ja foi processada ou nao pode mais ser cancelada.');
        }

        $lgpdRequest->update(['cancelled_at' => now()]);

        Log::info('LGPD_DELETION_CANCELLED', [
            'request_id' => $lgpdRequest->id,
            'user_id'    => $user->id,
        ]);

        return back()->with('success', 'Exclusao cancelada. Sua conta permanece ativa normalmente.');
    }
}
