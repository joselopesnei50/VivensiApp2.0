<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anexos polimorficos pra Asset (Patrimonio), InventoryItem (Almox/Estoque)
 * e InventoryMovement (Entradas/Saidas de Estoque).
 *
 * Storage: disk 'local' privado — storage/app/private/tenants/{tenantId}/attachments/{morphType}/{uuid}.{ext}
 * Nunca expor path direto ao publico. Todo acesso passa por download() que
 * checa tenant antes de servir.
 *
 * Mimes aceitos: PDF, JPG, PNG. Max 10 MB por arquivo.
 */
class AttachmentController extends Controller
{
    private const MAX_SIZE_KB = 10240; // 10 MB
    private const ACCEPTED_MIMES = 'pdf,jpg,jpeg,png';

    /**
     * Whitelist morphType → Model. Bloqueia IDOR via morphType arbitrario.
     * Slugs curtos (asset, inv_item, inv_move) sao publicos na URL — usa
     * o class name apenas internamente pra polimorfismo do Eloquent.
     */
    private const MORPH_MAP = [
        'asset'       => Asset::class,
        'inv_item'    => InventoryItem::class,
        'inv_move'    => InventoryMovement::class,
        'beneficiary' => Beneficiary::class,
    ];

    /**
     * Rotulos amigaveis por morphType — usados no titulo da view.
     */
    private const LABELS = [
        'asset'       => 'Patrimônio',
        'inv_item'    => 'Item de Estoque',
        'inv_move'    => 'Movimento de Estoque',
        'beneficiary' => 'Beneficiário',
    ];

    /**
     * URL de retorno por morphType — deterministico (nao usar url()->previous()
     * porque apos POST+back() o referer vira a propria pagina de anexos e o
     * "Voltar" cai em loop).
     */
    private const BACK_URLS = [
        'asset'       => '/ngo/assets',
        'inv_item'    => '/ngo/inventory',
        'inv_move'    => '/ngo/inventory',
        'beneficiary' => '/ngo/beneficiaries',
    ];

    /**
     * Tipos de documento aceitos em anexos de beneficiario. Lista fechada
     * (LGPD art. 6, principio da finalidade) — evita "documento.pdf" sem
     * contexto e alimenta filtro do relatorio anual. Mudanca aqui exige
     * revisar `attachments.tipo_documento` na base + view.
     */
    private const TIPOS_DOCUMENTO_BENEFICIARY = [
        'rg'                     => 'RG',
        'cpf'                    => 'CPF',
        'comprovante_residencia' => 'Comprovante de Residência',
        'nis_cadunico'           => 'NIS / CadÚnico',
        'laudo_medico'           => 'Laudo Médico',
        'certidao'               => 'Certidão',
        'termo_lgpd'             => 'Termo de Consentimento LGPD',
        'outros'                 => 'Outros',
    ];

    /**
     * Lista de anexos de um registro dono (+ formulario upload).
     * GET /attachments/{morphType}/{morphId}
     */
    public function index(string $morphType, int $morphId): View
    {
        $owner       = $this->resolveOwner($morphType, $morphId);
        $attachments = $owner->attachments()->latest()->get();

        // Beneficiario volta pro show do proprio registro; os demais voltam
        // pra listagem (que ja mostra o item no contexto certo).
        $backBase = self::BACK_URLS[$morphType] ?? '/';
        $backUrl  = $morphType === 'beneficiary' ? "{$backBase}/{$morphId}" : $backBase;

        $temTermoLgpd = $morphType === 'beneficiary'
            ? $attachments->contains(fn ($a) => $a->tipo_documento === 'termo_lgpd')
            : true;

        return view('attachments.index', [
            'owner'         => $owner,
            'morphType'     => $morphType,
            'morphId'       => $morphId,
            'label'         => self::LABELS[$morphType] ?? 'Registro',
            'attachments'   => $attachments,
            'maxSizeMb'     => (int) (self::MAX_SIZE_KB / 1024),
            'backUrl'       => $backUrl,
            'tiposDocumento'=> $morphType === 'beneficiary' ? self::TIPOS_DOCUMENTO_BENEFICIARY : [],
            'canDelete'     => $morphType === 'beneficiary'
                ? Gate::allows('delete-beneficiaries')
                : true,
            'temTermoLgpd'  => $temTermoLgpd,
        ]);
    }

    /**
     * Sobe um anexo pra um registro dono.
     * POST /attachments/{morphType}/{morphId}
     */
    public function store(Request $request, string $morphType, int $morphId): RedirectResponse
    {
        $owner = $this->resolveOwner($morphType, $morphId);

        $rules = [
            'file' => 'required|file|mimes:' . self::ACCEPTED_MIMES . '|max:' . self::MAX_SIZE_KB,
        ];

        // Beneficiario exige tipo_documento (lista fechada, LGPD principio da
        // finalidade). Demais morphs continuam livres — Conformidade tem fluxo
        // proprio (ConformidadeController) que ja preenche o campo.
        if ($morphType === 'beneficiary') {
            $rules['tipo_documento'] = 'required|in:' . implode(',', array_keys(self::TIPOS_DOCUMENTO_BENEFICIARY));
        }

        $validated = $request->validate($rules);

        // Gate LGPD: qualquer documento com PII de beneficiario (RG, CPF,
        // comprovante, NIS, laudo, certidao, outros) exige termo_lgpd previo
        // anexado ao mesmo beneficiario. So termo_lgpd pode ser subido "vazio".
        // Nao precisa de flag em coluna — usa o proprio anexo como consent.
        if ($morphType === 'beneficiary' && $validated['tipo_documento'] !== 'termo_lgpd') {
            $temTermo = $owner->attachments()
                ->where('tipo_documento', 'termo_lgpd')
                ->exists();
            if (! $temTermo) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'tipo_documento' => 'Anexe primeiro o Termo de Consentimento LGPD deste beneficiário. Sem consent registrado, não é permitido armazenar documentos com dados pessoais.',
                    ]);
            }
        }

        $file      = $request->file('file');
        $tenantId  = (int) auth()->user()->tenant_id;
        $ext       = strtolower($file->getClientOriginalExtension());
        $uuid      = (string) Str::uuid();
        $subdir    = "tenants/{$tenantId}/attachments/{$morphType}";
        $filename  = "{$uuid}.{$ext}";

        // storeAs no disk 'local' — cai em storage/app/private (private via config)
        $path = $file->storeAs($subdir, $filename, 'local');

        $attachment = Attachment::create([
            'tenant_id'       => $tenantId,
            'attachable_type' => get_class($owner),
            'attachable_id'   => $owner->id,
            'original_name'   => Str::limit($file->getClientOriginalName(), 255, ''),
            'path'            => $path,
            'mime_type'       => (string) $file->getMimeType(),
            'size_bytes'      => (int) $file->getSize(),
            'uploaded_by'     => auth()->id(),
            'tipo_documento'  => $validated['tipo_documento'] ?? null,
        ]);

        Log::info('ATTACHMENT_UPLOADED', [
            'attachment_id' => $attachment->id,
            'tenant_id'     => $tenantId,
            'morph_type'    => $morphType,
            'morph_id'      => $morphId,
            'size_bytes'    => $attachment->size_bytes,
            'user_id'       => auth()->id(),
        ]);

        if ($morphType === 'beneficiary') {
            $this->auditBeneficiary($request, 'BENEFICIARY_ATTACHMENT_UPLOADED', $attachment, [
                'morph_id'       => $morphId,
                'tipo_documento' => $attachment->tipo_documento,
                'size_bytes'     => $attachment->size_bytes,
                'mime_type'      => $attachment->mime_type,
                'original_name'  => $attachment->original_name,
            ]);
        }

        return back()->with('success', 'Anexo enviado.');
    }

    /**
     * Stream do anexo com tenant check.
     * GET /attachments/{id}/download
     */
    public function download(Request $request, int $id): Response
    {
        $tenantId = (int) auth()->user()->tenant_id;

        // BelongsToTenant no model ja filtra por tenant — belt+suspenders com where explicito
        $att = Attachment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // AuditLog: download de PII de terceiros (LGPD art. 37, registro de
        // operacoes). So loga quando o dono do anexo e Beneficiary — os demais
        // morphs (asset/inv_item/inv_move) nao contem PII pessoal.
        if ($att->attachable_type === Beneficiary::class) {
            $this->auditBeneficiary($request, 'BENEFICIARY_ATTACHMENT_DOWNLOADED', $att, [
                'morph_id'       => $att->attachable_id,
                'tipo_documento' => $att->tipo_documento,
                'original_name'  => $att->original_name,
            ]);
        }

        abort_unless(Storage::disk('local')->exists($att->path), 404);

        $mime = $att->mime_type ?: (Storage::disk('local')->mimeType($att->path) ?: 'application/octet-stream');
        $inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'], true);
        $headers = ['X-Content-Type-Options' => 'nosniff'];

        return $inline
            ? Storage::disk('local')->response($att->path, $att->original_name, $headers)
            : Storage::disk('local')->download($att->path, $att->original_name, $headers);
    }

    /**
     * Soft-delete do anexo. Arquivo fisico fica ate purge (roadmap futuro).
     * DELETE /attachments/{id}
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $att = Attachment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Anexo de Beneficiary so pode ser removido por quem tem gate
        // delete-beneficiaries (mesma regra da remocao do proprio cadastro).
        // Employee opera o modulo mas nao deleta PII.
        if ($att->attachable_type === Beneficiary::class && ! Gate::allows('delete-beneficiaries')) {
            abort(403, 'Sem permissão para remover anexos de beneficiário.');
        }

        $att->delete();

        Log::info('ATTACHMENT_DELETED', [
            'attachment_id' => $id,
            'tenant_id'     => $tenantId,
            'user_id'       => auth()->id(),
        ]);

        if ($att->attachable_type === Beneficiary::class) {
            $this->auditBeneficiary($request, 'BENEFICIARY_ATTACHMENT_DELETED', $att, [
                'morph_id'       => $att->attachable_id,
                'tipo_documento' => $att->tipo_documento,
                'original_name'  => $att->original_name,
            ]);
        }

        return back()->with('success', 'Anexo removido.');
    }

    /**
     * Registra evento sensivel de PII no AuditLog (LGPD art. 37). Usado apenas
     * pra anexos de Beneficiary — os demais morphs (asset/inv_item/inv_move)
     * seguem so com Log::info.
     */
    private function auditBeneficiary(Request $request, string $event, Attachment $att, array $extra = []): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $att->tenant_id,
                'user_id'        => auth()->id(),
                'event'          => $event,
                'auditable_type' => Attachment::class,
                'auditable_id'   => $att->id,
                'new_values'     => $extra,
                'ip_address'     => $request->ip(),
                'user_agent'     => Str::limit((string) $request->userAgent(), 500, ''),
                'url'            => Str::limit((string) $request->fullUrl(), 500, ''),
                'session_id'     => $request->hasSession() ? $request->session()->getId() : null,
            ]);
        } catch (\Throwable $e) {
            // AuditLog nao pode quebrar fluxo do usuario — so registra e segue.
            Log::warning('AUDITLOG_FAILED', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Resolve owner via whitelist + tenant check. 404 pra morphType desconhecido
     * ou registro de outro tenant (evita IDOR e enumeracao cross-tenant).
     */
    private function resolveOwner(string $morphType, int $morphId): Model
    {
        abort_unless(isset(self::MORPH_MAP[$morphType]), 404);

        $class = self::MORPH_MAP[$morphType];
        $tenantId = (int) auth()->user()->tenant_id;

        // Todos os 3 models usam BelongsToTenant → global scope ja filtra por tenant.
        // Where explicito reforça caso alguem chame com withoutGlobalScope no futuro.
        return $class::where('id', $morphId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }
}
