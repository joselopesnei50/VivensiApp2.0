<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        'asset'    => Asset::class,
        'inv_item' => InventoryItem::class,
        'inv_move' => InventoryMovement::class,
    ];

    /**
     * Sobe um anexo pra um registro dono.
     * POST /attachments/{morphType}/{morphId}
     */
    public function store(Request $request, string $morphType, int $morphId): RedirectResponse
    {
        $owner = $this->resolveOwner($morphType, $morphId);

        $request->validate([
            'file' => 'required|file|mimes:' . self::ACCEPTED_MIMES . '|max:' . self::MAX_SIZE_KB,
        ]);

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
        ]);

        Log::info('ATTACHMENT_UPLOADED', [
            'attachment_id' => $attachment->id,
            'tenant_id'     => $tenantId,
            'morph_type'    => $morphType,
            'morph_id'      => $morphId,
            'size_bytes'    => $attachment->size_bytes,
            'user_id'       => auth()->id(),
        ]);

        return back()->with('success', 'Anexo enviado.');
    }

    /**
     * Stream do anexo com tenant check.
     * GET /attachments/{id}/download
     */
    public function download(int $id): Response
    {
        $tenantId = (int) auth()->user()->tenant_id;

        // BelongsToTenant no model ja filtra por tenant — belt+suspenders com where explicito
        $att = Attachment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

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
    public function destroy(int $id): RedirectResponse
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $att = Attachment::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $att->delete();

        Log::info('ATTACHMENT_DELETED', [
            'attachment_id' => $id,
            'tenant_id'     => $tenantId,
            'user_id'       => auth()->id(),
        ]);

        return back()->with('success', 'Anexo removido.');
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
