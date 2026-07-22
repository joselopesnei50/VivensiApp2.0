<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Jobs\RecalcularConformidadeJob;
use App\Models\Attachment;
use App\Models\AvaliacaoRequisito;
use App\Models\CicloConformidade;
use App\Models\RequisitoLegal;
use App\Models\SnapshotConformidade;
use App\Models\Tenant;
use App\Services\ComplianceCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConformidadeController extends Controller
{
    public function __construct(private ComplianceCalculationService $service) {}

    public function dashboard()
    {
        $this->autorizarAdmin();

        $tenantId  = auth()->user()->tenant_id;
        $dashboard = $this->service->dashboard($tenantId);

        $historico = SnapshotConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('snapshotado_em')
            ->limit(12)
            ->get(['indice_geral', 'snapshotado_em']);

        return view('ngo.conformidade.dashboard', [
            'dashboard' => $dashboard,
            'historico' => $historico,
        ]);
    }

    public function eixo(string $eixo)
    {
        $this->autorizarAdmin();

        $eixosValidos = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao', 'mrosc', 'suas'];
        abort_unless(in_array($eixo, $eixosValidos), 404);

        $tenantId  = auth()->user()->tenant_id;
        $dashboard = $this->service->dashboard($tenantId);

        $ciclo     = $dashboard['ciclos'][$eixo] ?? null;
        $indices   = $dashboard['indices_por_eixo'][$eixo] ?? null;

        $requisitos = RequisitoLegal::with('regra')
            ->where('eixo', $eixo)
            ->where('ativo', true)
            ->get();

        $avaliacoesPorCodigo = collect($dashboard['avaliacoes'])
            ->keyBy('codigo');

        return view('ngo.conformidade.eixo', [
            'eixo'                => $eixo,
            'ciclo'               => $ciclo,
            'indices'             => $indices,
            'requisitos'          => $requisitos,
            'avaliacoesPorCodigo' => $avaliacoesPorCodigo,
        ]);
    }

    public function declarar(Request $request, int $requisito)
    {
        $this->autorizarSoAdmin();

        $req = RequisitoLegal::where('tipo', 'C')->findOrFail($requisito);

        $validated = $request->validate([
            'observacoes' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $ciclo = CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('eixo', $req->eixo)
            ->where('status', 'em_andamento')
            ->firstOrFail();

        AvaliacaoRequisito::create([
            'tenant_id'            => $tenantId,
            'ciclo_conformidade_id' => $ciclo->id,
            'requisito_legal_id'   => $req->id,
            'resultado'            => 'verde',
            'avaliado_em'          => now(),
            'avaliado_por'         => auth()->id(),
            'observacoes'          => $validated['observacoes'],
        ]);

        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Declaração registrada com sucesso.');
    }

    public function atualizarCiclo(Request $request)
    {
        $this->autorizarAdmin();

        $validated = $request->validate([
            'eixo'        => ['required', 'string', 'in:cebas_geral,cebas_as,cebas_saude,cebas_educacao,mrosc,suas'],
            'data_inicio' => ['required', 'date'],
            'data_fim'    => ['required', 'date', 'after:data_inicio'],
            'enquadramento' => ['nullable', 'string', 'in:3_anos,5_anos,anual,por_parceria'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('eixo', $validated['eixo'])
            ->where('status', 'em_andamento')
            ->update(['status' => 'encerrado']);

        CicloConformidade::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'status'    => 'em_andamento',
        ]));

        $this->service->invalidarCache($tenantId);
        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Ciclo atualizado.');
    }

    public function recalcular()
    {
        $this->autorizarAdmin();

        $tenantId = auth()->user()->tenant_id;
        $this->service->invalidarCache($tenantId);
        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Recálculo agendado. Atualize a página em alguns instantes.');
    }

    public function uploadForm(int $requisito)
    {
        $this->autorizarSoAdmin();

        $req = RequisitoLegal::with('regra')->where('tipo', 'B')->findOrFail($requisito);

        $tenantId = auth()->user()->tenant_id;
        $historico = [];

        if ($req->regra?->tipo_documento_obrigatorio) {
            $historico = Attachment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('tipo_documento', $req->regra->tipo_documento_obrigatorio)
                ->whereNull('deleted_at')
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('ngo.conformidade.upload', [
            'requisito' => $req,
            'historico' => $historico,
        ]);
    }

    public function uploadDocumento(Request $request, int $requisito): RedirectResponse
    {
        $this->autorizarSoAdmin();

        $req = RequisitoLegal::with('regra')->where('tipo', 'B')->findOrFail($requisito);
        abort_unless($req->regra && $req->regra->tipo_documento_obrigatorio, 422);

        $validated = $request->validate([
            'arquivo'     => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $file     = $request->file('arquivo');
        $ext      = $file->getClientOriginalExtension();
        $uuid     = (string) Str::uuid();
        $path     = "private/tenants/{$tenantId}/conformidade/{$uuid}.{$ext}";

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $anterior = Attachment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('tipo_documento', $req->regra->tipo_documento_obrigatorio)
            ->whereNull('deleted_at')
            ->whereNull('substituido_por_id')
            ->latest()
            ->first();

        $novoDoc = Attachment::create([
            'tenant_id'        => $tenantId,
            'attachable_type'  => Tenant::class,
            'attachable_id'    => $tenantId,
            'original_name'    => $file->getClientOriginalName(),
            'path'             => $path,
            'mime_type'        => $file->getMimeType(),
            'size_bytes'       => $file->getSize(),
            'uploaded_by'      => auth()->id(),
            'tipo_documento'   => $req->regra->tipo_documento_obrigatorio,
            'valid_until'      => $validated['valid_until'] ?? null,
            'versao'           => $anterior ? (($anterior->versao ?? 1) + 1) : 1,
            'alerta_enviado_em' => null,
        ]);

        if ($anterior) {
            $anterior->update(['substituido_por_id' => $novoDoc->id]);
        }

        $this->service->invalidarCache($tenantId);
        RecalcularConformidadeJob::dispatch($tenantId);

        return back()->with('success', 'Documento enviado com sucesso. O índice de conformidade será recalculado em instantes.');
    }

    public function downloadDocumento(int $attachment): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $doc = Attachment::withoutGlobalScopes()
            ->where('id', $attachment)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        // Expõe path apenas internamente para o download
        $path = $doc->getRawOriginal('path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $doc->original_name);
    }

    private function autorizarAdmin(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['ngo', 'manager', 'super_admin']),
            403
        );
    }

    private function autorizarSoAdmin(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['ngo', 'super_admin']),
            403
        );
    }
}
