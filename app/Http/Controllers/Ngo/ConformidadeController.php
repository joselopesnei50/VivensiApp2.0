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
use App\Services\CnpjApiService;
use App\Services\ComplianceCalculationService;
use App\Services\RelatorioPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConformidadeController extends Controller
{
    public function __construct(
        private ComplianceCalculationService $service,
        private RelatorioPdfService $relatorio,
        private CnpjApiService $cnpjApi,
    ) {}

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

    public function pdfRma(Request $request)
    {
        $this->autorizarAdmin();

        $validated = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'ano' => ['required', 'integer', 'min:2020', 'max:' . now()->year],
        ]);

        $dados = $this->relatorio->dadosRma(
            auth()->user()->tenant_id,
            (int) $validated['mes'],
            (int) $validated['ano'],
        );

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.conformidade.pdf.rma', $dados);

        $filename = 'rma-' . str_pad($dados['mes'], 2, '0', STR_PAD_LEFT) . '-' . $dados['ano'] . '.pdf';

        return $pdf->download($filename);
    }

    public function pdfCebas()
    {
        $this->autorizarAdmin();

        $dados = $this->relatorio->dadosCebas(auth()->user()->tenant_id);

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.conformidade.pdf.dossie_cebas', $dados);

        return $pdf->download('dossie-cebas-' . now()->format('Y-m-d') . '.pdf');
    }

    public function pdfMrosc()
    {
        $this->autorizarAdmin();

        $dados = $this->relatorio->dadosMrosc(auth()->user()->tenant_id);

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.conformidade.pdf.relatorio_mrosc', $dados);

        return $pdf->download('relatorio-mrosc-' . now()->format('Y-m-d') . '.pdf');
    }

    public function downloadDocumento(int $attachment)
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

    public function configurar()
    {
        $this->autorizarSoAdmin();

        $tenant = Tenant::withoutGlobalScopes()->findOrFail(auth()->user()->tenant_id);

        return view('ngo.conformidade.configurar', compact('tenant'));
    }

    public function salvarConfigurar(Request $request): RedirectResponse
    {
        $this->autorizarSoAdmin();

        $validated = $request->validate([
            'cnas_numero'          => ['nullable', 'string', 'max:30'],
            'cnas_validade'        => ['nullable', 'date'],
            'cmas_numero'          => ['nullable', 'string', 'max:30'],
            'cmas_validade'        => ['nullable', 'date'],
            'cneas_codigo'         => ['nullable', 'string', 'max:30'],
            'area_atuacao_cebas'   => ['nullable', 'string', 'in:assistencia_social,saude,educacao'],
            'data_fundacao'        => ['nullable', 'date', 'before:today'],
            'cnae_principal'       => ['nullable', 'string', 'max:20'],
            'receita_bruta_anual_ref' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        Tenant::withoutGlobalScopes()->where('id', $tenantId)->update($validated);

        $this->service->invalidarCache($tenantId);

        return back()->with('success', 'Perfil de conformidade atualizado com sucesso.');
    }

    public function cnpjLookup(Request $request)
    {
        $this->autorizarSoAdmin();

        $cnpj = preg_replace('/\D/', '', (string) $request->input('cnpj', ''));

        if (strlen($cnpj) !== 14) {
            return response()->json(['error' => 'CNPJ inválido.'], 422);
        }

        $dados = $this->cnpjApi->consultar($cnpj);

        if (! $dados) {
            return response()->json(['error' => 'CNPJ não encontrado ou serviço indisponível.'], 404);
        }

        return response()->json($dados);
    }

    public function ciclos()
    {
        $this->autorizarAdmin();

        $tenantId = auth()->user()->tenant_id;

        $eixos = ['cebas_geral', 'cebas_as', 'cebas_saude', 'cebas_educacao', 'mrosc', 'suas'];

        $ciclosPorEixo = CicloConformidade::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('eixo');

        return view('ngo.conformidade.ciclos', compact('eixos', 'ciclosPorEixo'));
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
