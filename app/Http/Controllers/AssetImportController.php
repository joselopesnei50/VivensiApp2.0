<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import de planilha CSV de Patrimonio (assets) — painel NGO.
 *
 * Headers fixos (case-insensitive, acentos ignorados):
 *   nome            (obrigatorio)
 *   codigo          (opcional, chave de dedup)
 *   data_aquisicao  (obrigatorio, dd/mm/aaaa ou aaaa-mm-dd)
 *   valor           (obrigatorio, aceita "R$ 1.234,56" ou "1234.56")
 *   descricao       (opcional)
 *   vida_util_anos  (opcional, inteiro)
 *   valor_residual  (opcional, decimal)
 *   status          (opcional, default: active)
 *   local           (opcional)
 *   responsavel     (opcional)
 *
 * Fluxo: showForm → preview (post CSV) → import (post confirm).
 * Idempotencia por (tenant, code) — evita duplicar bens com mesmo patrimonio.
 * Sem aprovacao — o user decidiu que patrimonio nao passa por gestor.
 */
class AssetImportController extends Controller
{
    private const MAX_ROWS = 5000;

    private const HEADER_ALIASES = [
        'nome'           => ['nome', 'name', 'bem', 'item'],
        'codigo'         => ['codigo', 'código', 'code', 'cod', 'patrimonio', 'patrimônio'],
        'data_aquisicao' => ['data_aquisicao', 'data_aquisição', 'aquisicao', 'aquisição', 'data', 'acquisition_date'],
        'valor'          => ['valor', 'value', 'preco', 'preço', 'custo'],
        'descricao'      => ['descricao', 'descrição', 'description', 'desc'],
        'vida_util_anos' => ['vida_util_anos', 'vida_útil_anos', 'vida_util', 'useful_life_years', 'anos'],
        'valor_residual' => ['valor_residual', 'residual_value', 'residual'],
        'status'         => ['status', 'situacao', 'situação'],
        'local'          => ['local', 'localizacao', 'localização', 'location', 'onde'],
        'responsavel'    => ['responsavel', 'responsável', 'responsible', 'dono'],
    ];

    private const STATUS_MAP = [
        'ativo'       => 'active',
        'active'      => 'active',
        'manutencao'  => 'maintenance',
        'manutenção'  => 'maintenance',
        'maintenance' => 'maintenance',
        'baixado'     => 'disposed',
        'disposed'    => 'disposed',
        'perdido'     => 'lost',
        'lost'        => 'lost',
    ];

    public function showForm(): View
    {
        return view('ngo.assets.import.form');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $csv = "nome,codigo,data_aquisicao,valor,descricao,vida_util_anos,valor_residual,status,local,responsavel\n"
             . "\"Notebook Dell Vostro\",PAT-001,15/01/2026,4500.00,\"Uso do coordenador\",5,500,ativo,\"Sala 1\",\"Ana Silva\"\n"
             . "\"Cadeira ergonomica\",PAT-002,20/02/2026,\"R\$ 899,90\",\"\",10,50,ativo,\"Sala 2\",\n";

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv;
        }, 'template-patrimonio-vivensi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $parsed = $this->parseCsv($request->file('file')->getRealPath());

        if (isset($parsed['error'])) {
            return back()->with('error', $parsed['error']);
        }

        session()->put('asset_import.rows', $parsed['rows']);

        return view('ngo.assets.import.preview', [
            'rows'        => $parsed['rows'],
            'parseErrors' => $parsed['errors'],
            'summary'     => [
                'total'  => count($parsed['rows']),
                'valid'  => count(array_filter($parsed['rows'], fn ($r) => empty($r['_error']))),
                'errors' => count($parsed['errors']),
            ],
        ]);
    }

    public function import(): RedirectResponse
    {
        $rows = session()->pull('asset_import.rows', []);

        if (empty($rows)) {
            return redirect()->route('assets.import.form')
                ->with('error', 'Sessão expirou. Faça upload novamente.');
        }

        $tenantId = (int) auth()->user()->tenant_id;
        $stats    = ['created' => 0, 'duplicated' => 0, 'errors' => 0];

        DB::transaction(function () use ($rows, $tenantId, &$stats) {
            foreach ($rows as $row) {
                if (!empty($row['_error'])) {
                    $stats['errors']++;
                    continue;
                }

                // Dedup por code — so quando code nao esta vazio
                if (!empty($row['codigo'])) {
                    $exists = Asset::withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenantId)
                        ->where('code', $row['codigo'])
                        ->exists();

                    if ($exists) {
                        $stats['duplicated']++;
                        continue;
                    }
                }

                Asset::create([
                    'tenant_id'         => $tenantId,
                    'name'              => $row['nome'],
                    'code'              => ($row['codigo'] ?? '') ?: null,
                    'description'       => ($row['descricao'] ?? '') ?: null,
                    'acquisition_date'  => $row['data_aquisicao'],
                    'value'             => $row['valor'],
                    'useful_life_years' => $row['vida_util_anos'] ?? null,
                    'residual_value'    => $row['valor_residual'] ?? null,
                    'status'            => $row['status'] ?? 'active',
                    'location'          => ($row['local'] ?? '') ?: null,
                    'responsible'       => ($row['responsavel'] ?? '') ?: null,
                ]);

                $stats['created']++;
            }
        });

        Log::info('ASSET_IMPORT_COMPLETED', array_merge($stats, [
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
        ]));

        return redirect()->route('assets.import.form')->with('success', sprintf(
            '%d bens criados · %d duplicados ignorados · %d com erro.',
            $stats['created'], $stats['duplicated'], $stats['errors']
        ));
    }

    // ── Parsing ─────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return ['error' => 'Não foi possível abrir o arquivo.'];
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $delimiter = $this->detectDelimiter($path);
        $headerRaw = fgetcsv($handle, 0, $delimiter);

        if (!$headerRaw) {
            fclose($handle);
            return ['error' => 'CSV vazio ou cabeçalho inválido.'];
        }

        $headerMap = $this->mapHeaders($headerRaw);
        $missing   = array_diff(['nome', 'data_aquisicao', 'valor'], array_keys($headerMap));
        if (!empty($missing)) {
            fclose($handle);
            return ['error' => 'Faltam colunas obrigatórias: ' . implode(', ', $missing)];
        }

        $rows   = [];
        $errors = [];
        $rowNum = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count(array_filter($cells, fn ($c) => $c !== '')) === 0) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = 'Limite de ' . self::MAX_ROWS . ' linhas atingido — resto ignorado.';
                break;
            }

            $row = ['_line' => $rowNum, '_error' => null];
            foreach ($headerMap as $canonical => $colIdx) {
                $row[$canonical] = trim($cells[$colIdx] ?? '');
            }

            $err = $this->validateAndNormalize($row);
            if ($err) {
                $row['_error'] = $err;
                $errors[]      = "Linha $rowNum: $err";
            }

            $rows[] = $row;
        }

        fclose($handle);
        return ['rows' => $rows, 'errors' => $errors];
    }

    private function detectDelimiter(string $path): string
    {
        $sample = @file_get_contents($path, false, null, 0, 4096) ?: '';
        $counts = [
            ','  => substr_count($sample, ','),
            ';'  => substr_count($sample, ';'),
            "\t" => substr_count($sample, "\t"),
        ];
        arsort($counts);
        return array_key_first($counts);
    }

    private function mapHeaders(array $raw): array
    {
        $map = [];
        foreach ($raw as $idx => $cell) {
            $norm = $this->normalize($cell);
            foreach (self::HEADER_ALIASES as $canonical => $aliases) {
                if (in_array($norm, array_map([$this, 'normalize'], $aliases), true)) {
                    $map[$canonical] = $idx;
                    break;
                }
            }
        }
        return $map;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        return strtr($s, [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','ê'=>'e','è'=>'e','ë'=>'e',
            'í'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c',
        ]);
    }

    private function validateAndNormalize(array &$row): ?string
    {
        // Nome
        if (empty($row['nome'])) {
            return 'nome vazio';
        }
        if (mb_strlen($row['nome']) > 255) {
            return 'nome > 255 chars';
        }

        // Valor
        $rawValor = $row['valor'] ?? '';
        $clean    = preg_replace('/[^\d,.\-]/', '', $rawValor);
        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        $valor = (float) $clean;
        if ($valor <= 0) {
            return 'valor inválido';
        }
        $row['valor'] = $valor;

        // Data aquisicao
        $rawData = $row['data_aquisicao'] ?? '';
        $data = null;
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y'] as $fmt) {
            try {
                $data = Carbon::createFromFormat($fmt, $rawData);
                if ($data) break;
            } catch (\Throwable) {}
        }
        if (!$data) {
            return 'data_aquisicao inválida (use dd/mm/aaaa)';
        }
        $row['data_aquisicao'] = $data->format('Y-m-d');

        // Vida util (opcional)
        if (!empty($row['vida_util_anos'])) {
            $vu = (int) $row['vida_util_anos'];
            $row['vida_util_anos'] = $vu > 0 ? $vu : null;
        } else {
            $row['vida_util_anos'] = null;
        }

        // Valor residual (opcional)
        if (!empty($row['valor_residual'])) {
            $rv = preg_replace('/[^\d,.\-]/', '', $row['valor_residual']);
            if (str_contains($rv, ',')) {
                $rv = str_replace('.', '', $rv);
                $rv = str_replace(',', '.', $rv);
            }
            $row['valor_residual'] = (float) $rv;
        } else {
            $row['valor_residual'] = null;
        }

        // Status (opcional, default 'active')
        $rawStatus = $this->normalize($row['status'] ?? '');
        $row['status'] = $rawStatus === '' ? 'active' : (self::STATUS_MAP[$rawStatus] ?? 'active');

        // Codigo — truncate se muito longo
        if (!empty($row['codigo'])) {
            $row['codigo'] = Str::limit($row['codigo'], 100, '');
        }

        return null;
    }
}
