<?php

namespace App\Jobs;

use App\Mail\LgpdExportReadyMail;
use App\Models\LgpdDataRequest;
use App\Models\LoginActivity;
use App\Models\MeetingBooking;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * LGPD art. 18 IV — portabilidade / exportacao dos dados do titular.
 *
 * Coleta TODOS os dados pessoais do usuario em JSON e empacota em ZIP.
 * Salva em disco privado (nao acessivel via URL direta); usuario baixa
 * via link tokenizado com validade de 48h.
 *
 * Categorias exportadas:
 *   - profile            → dados da conta (name, email, phone, roles)
 *   - login_activity     → historico de logins (ultimos 90d)
 *   - whatsapp_chats     → conversas onde o user participou
 *   - whatsapp_messages  → mensagens (limite 5000 mais recentes)
 *   - meeting_bookings   → agendamentos (quando user e o cliente)
 *   - lgpd_requests      → historico de solicitacoes LGPD do proprio user
 *   - consents           → registros de consentimento LGPD
 */
class ExportUserLgpdDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(protected int $requestId)
    {
        $this->onQueue('lgpd');
    }

    public function handle(): void
    {
        $request = LgpdDataRequest::withoutGlobalScope('tenant')->find($this->requestId);

        if (!$request || !$request->isExport()) {
            Log::warning('LGPD Export: request nao encontrado ou tipo invalido', ['id' => $this->requestId]);
            return;
        }

        $user = User::find($request->user_id);
        if (!$user) {
            $request->update(['status' => LgpdDataRequest::STATUS_REJECTED, 'notes' => 'Usuario nao encontrado.']);
            return;
        }

        $request->update(['status' => LgpdDataRequest::STATUS_PROCESSING]);

        try {
            $payload = $this->collectUserData($user);
            $zipPath = $this->packageZip($user, $payload);

            $token = $request->generateExportToken();

            $request->update([
                'status'           => LgpdDataRequest::STATUS_COMPLETED,
                'processed_at'     => now(),
                'export_file_path' => $zipPath,
            ]);

            Log::info('LGPD_EXPORT_READY', [
                'request_id' => $request->id,
                'user_id'    => $user->id,
                'file_size'  => Storage::disk('local')->size($zipPath),
            ]);

            Mail::to($user->email)->send(new LgpdExportReadyMail($user, $request, $token));
        } catch (\Throwable $e) {
            Log::error('LGPD_EXPORT_FAILED', [
                'request_id' => $request->id,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
            ]);
            $request->update([
                'status' => LgpdDataRequest::STATUS_REJECTED,
                'notes'  => 'Erro ao gerar arquivo: ' . $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Monta o payload de exportacao com todas categorias.
     * @return array<string, mixed>
     */
    private function collectUserData(User $user): array
    {
        return [
            '_meta' => [
                'export_generated_at' => now()->toIso8601String(),
                'export_law'          => 'LGPD Lei 13.709/2018 art. 18 IV',
                'controller'          => 'NC5 HUB DIGITAL LTDA (Vivensi)',
                'contact_dpo'         => 'dpo@vivensi.com.br',
            ],
            'profile' => [
                'id'                       => $user->id,
                'name'                     => $user->name,
                'email'                    => $user->email,
                'phone'                    => $user->phone,
                'role'                     => $user->role,
                'tenant_id'                => $user->tenant_id,
                'status'                   => $user->status,
                'created_at'               => $user->created_at?->toIso8601String(),
                'last_login_at'            => $user->last_login_at?->toIso8601String(),
                'terms_accepted_at'        => $user->terms_accepted_at?->toIso8601String(),
                'onboarding_completed_at'  => $user->onboarding_completed_at?->toIso8601String(),
                'two_factor_enabled'       => $user->hasTwoFactorEnabled(),
            ],
            'login_activity'    => $this->safeCollect(fn() => LoginActivity::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->latest()
                ->limit(500)
                ->get(['created_at', 'ip_address', 'user_agent', 'success'])
                ->toArray()),
            'whatsapp_chats'    => $this->safeCollect(fn() => WhatsappChat::withoutGlobalScope('tenant')
                ->where('tenant_id', $user->tenant_id)
                ->limit(200)
                ->get(['id', 'wa_id', 'contact_name', 'status', 'last_message_at', 'created_at'])
                ->toArray()),
            'whatsapp_messages' => $this->safeCollect(fn() => WhatsappMessage::withoutGlobalScope('tenant')
                ->where('tenant_id', $user->tenant_id)
                ->latest()
                ->limit(5000)
                ->get(['id', 'chat_id', 'content', 'direction', 'type', 'status', 'created_at'])
                ->toArray()),
            'meeting_bookings'  => $this->safeCollect(fn() => MeetingBooking::where('email', $user->email)
                ->get(['name', 'email', 'phone', 'meeting_date', 'meeting_time', 'status', 'created_at'])
                ->toArray()),
            'lgpd_requests'     => $this->safeCollect(fn() => LgpdDataRequest::withoutGlobalScope('tenant')
                ->where('user_id', $user->id)
                ->get(['type', 'status', 'ip_address', 'created_at', 'processed_at'])
                ->toArray()),
        ];
    }

    /**
     * Executa collector; se model nao existe ou falha, retorna array vazio
     * e loga warning. Nunca deixa erro em uma categoria bloquear o resto.
     */
    private function safeCollect(callable $callback): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::warning('LGPD_EXPORT_CATEGORY_FAILED', ['error' => $e->getMessage()]);
            return ['_error' => 'Nao foi possivel coletar esta categoria: ' . $e->getMessage()];
        }
    }

    /**
     * Gera ZIP contendo:
     *   - data.json (payload completo)
     *   - README.txt (explicacao pro titular)
     * Retorna path relativo em storage/app (disco 'local' privado).
     */
    private function packageZip(User $user, array $payload): string
    {
        $filename = sprintf('lgpd-export-user-%d-%s.zip', $user->id, now()->format('YmdHis'));
        $relativePath = 'lgpd-exports/' . $filename;
        $fullPath = Storage::disk('local')->path($relativePath);

        Storage::disk('local')->makeDirectory('lgpd-exports');

        $zip = new ZipArchive();
        if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Falha ao criar ZIP em {$fullPath}");
        }

        $zip->addFromString('data.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('README.txt', $this->buildReadme($user));
        $zip->close();

        return $relativePath;
    }

    private function buildReadme(User $user): string
    {
        $now = now()->format('d/m/Y H:i');
        return <<<TXT
        EXPORTACAO DE DADOS PESSOAIS — LGPD
        ====================================

        Gerado em: {$now}
        Titular:   {$user->name} ({$user->email})
        Base legal: Art. 18 IV, Lei 13.709/2018 (LGPD) — direito a portabilidade.

        Conteudo:
          data.json  — todos os seus dados em formato estruturado (UTF-8, JSON)
          README.txt — este arquivo

        Categorias:
          profile           — dados da sua conta
          login_activity    — historico de logins (ultimos 90 dias)
          whatsapp_chats    — conversas do seu tenant
          whatsapp_messages — mensagens (ate 5000 mais recentes)
          meeting_bookings  — agendamentos vinculados ao seu e-mail
          lgpd_requests     — historico das suas solicitacoes LGPD

        Para exercer outros direitos previstos na LGPD:
          Contato DPO: dpo@vivensi.com.br
          Site:        https://vivensi.com.br

        Controlador dos Dados: NC5 HUB DIGITAL LTDA
        CNPJ: 67.848.807/0001-50
        TXT;
    }
}
