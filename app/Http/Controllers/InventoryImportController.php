<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import de planilha CSV de itens de Almoxarifado/Estoque (inventory_items) — painel NGO.
 *
 * Headers (case-insensitive, acentos ignorados):
 *   nome            (obrigatorio)
 *   sku             (opcional, chave primaria de dedup)
 *   unidade         (opcional, default 'un')
 *   quantidade      (obrigatorio, decimal)
 *   estoque_minimo  (opcional, default 0)
 *   valor_unitario  (opcional)
 *   descricao       (opcional)
 *   validade        (opcional, dd/mm/aaaa)
 *
 * Dedup: sku exato quando presente; sem sku, dedup por (name + unit).
 * Sem aprovacao — user decidiu.
 */
class InventoryImportController extends Controller
{
    private const MAX_ROWS = 5000;

    private const HEADER_ALIASES = [
        'nome'           => ['nome', 'name', 'item', 'produto'],
        'sku'            => ['sku', 'codigo', 'código', 'code', 'ref', 'referencia', 'referência'],
        'unidade'        => ['unidade', 'unit', 'un', 'medida'],
        'quantidade'     => ['quantidade', 'qtd', 'quantity', 'estoque'],
        'estoque_minimo' => ['estoque_minimo', 'estoque_mínimo', 'minimo', 'mínimo', 'minimum_stock', 'min'],
        'valor_unitario' => ['valor_unitario', 'valor_unitário', 'valor', 'preco', 'preço', 'value_per_unit'],
        'descricao'      => ['descricao', 'descrição', 'description', 'desc'],
        'validade'       => ['validade', 'expires_at', 'expiration', 'vencimento'],
    ];

    public function showForm(): View
    {
        return view('ngo.inventory.import.form');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $csv = "nome,sku,unidade,quantidade,estoque_minimo,valor_unitario,descricao,validade\n"
             . "\"Papel A4\",SKU-A4,resma,50,10,25.90,\"75g branco\",\n"
             . "\"Cesta basica\",SKU-CB,unidade,120,20,89.90,,31/12/2026\n";

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv;
        }, 'template-estoque-vivensi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $parsed = $this->parseCsv($request->file('file')->getRealPath());

        if (isset($parsed['error'])) {
            return back()->with('error', $parsed['error']);
        }

        session()->put('inventory_import.rows', $parsed['rows']);

        return view('ngo.inventory.import.preview', [
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
        $rows = session()->pull('inventory_import.rows', []);

        if (empty($rows)) {
            return redirect()->route('inventory.import.form')
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

                // Dedup: por sku quando presente; caso contrario por name+unit
                $q = InventoryItem::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId);

                if (!empty($row['sku'])) {
                    $q->where('sku', $row['sku']);
                } else {
                    $q->where('name', $row['nome'])
                      ->where('unit', $row['unidade']);
                }

                if ($q->exists()) {
                    $stats['duplicated']++;
                    continue;
                }

                InventoryItem::create([
                    'tenant_id'      => $tenantId,
                    'name'           => $row['nome'],
                    'sku'            => ($row['sku'] ?? '') ?: null,
                    'unit'           => $row['unidade'],
                    'quantity'       => $row['quantidade'],
                    'minimum_stock'  => $row['estoque_minimo'] ?? 0,
                    'value_per_unit' => $row['valor_unitario'] ?? null,
                    'description'    => ($row['descricao'] ?? '') ?: null,
                    'expires_at'     => $row['validade'] ?? null,
                ]);

                $stats['created']++;
            }
        });

        Log::info('INVENTORY_IMPORT_COMPLETED', array_merge($stats, [
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
        ]));

        return redirect()->route('inventory.import.form')->with('success', sprintf(
            '%d itens criados · %d duplicados ignorados · %d com erro.',
            $stats['created'], $stats['duplicated'], $stats['errors']
        ));
    }

    // ── Parsing ────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $handle = @fopen($path, 'r');
        if (!$handle) return ['error' => 'Não foi possível abrir o arquivo.'];

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $delimiter = $this->detectDelimiter($path);
        $headerRaw = fgetcsv($handle, 0, $delimiter);
        if (!$headerRaw) { fclose($handle); return ['error' => 'CSV vazio ou cabeçalho inválido.']; }

        $headerMap = $this->mapHeaders($headerRaw);
        $missing   = array_diff(['nome', 'quantidade'], array_keys($headerMap));
        if (!empty($missing)) { fclose($handle); return ['error' => 'Faltam colunas obrigatórias: ' . implode(', ', $missing)]; }

        $rows = []; $errors = []; $rowNum = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count(array_filter($cells, fn ($c) => $c !== '')) === 0) continue;
            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = 'Limite de ' . self::MAX_ROWS . ' linhas atingido.';
                break;
            }

            $row = ['_line' => $rowNum, '_error' => null];
            foreach ($headerMap as $canonical => $colIdx) {
                $row[$canonical] = trim($cells[$colIdx] ?? '');
            }

            $err = $this->validateAndNormalize($row);
            if ($err) { $row['_error'] = $err; $errors[] = "Linha $rowNum: $err"; }
            $rows[] = $row;
        }

        fclose($handle);
        return ['rows' => $rows, 'errors' => $errors];
    }

    private function detectDelimiter(string $path): string
    {
        $sample = @file_get_contents($path, false, null, 0, 4096) ?: '';
        $counts = [',' => substr_count($sample, ','), ';' => substr_count($sample, ';'), "\t" => substr_count($sample, "\t")];
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
                    $map[$canonical] = $idx; break;
                }
            }
        }
        return $map;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        return strtr($s, [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e','ë'=>'e',
            'í'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
        ]);
    }

    private function validateAndNormalize(array &$row): ?string
    {
        // Nome
        if (empty($row['nome'])) return 'nome vazio';
        if (mb_strlen($row['nome']) > 255) return 'nome > 255 chars';

        // Quantidade
        $rawQtd = $row['quantidade'] ?? '';
        $clean = preg_replace('/[^\d,.\-]/', '', $rawQtd);
        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        $qtd = (float) $clean;
        if ($qtd < 0) return 'quantidade inválida';
        $row['quantidade'] = $qtd;

        // Unidade (default 'un')
        $row['unidade'] = !empty($row['unidade']) ? Str::limit($row['unidade'], 20, '') : 'un';

        // Estoque minimo (default 0)
        if (!empty($row['estoque_minimo'])) {
            $c = preg_replace('/[^\d,.\-]/', '', $row['estoque_minimo']);
            if (str_contains($c, ',')) { $c = str_replace('.', '', $c); $c = str_replace(',', '.', $c); }
            $row['estoque_minimo'] = (float) $c;
        } else {
            $row['estoque_minimo'] = 0;
        }

        // Valor unitario (opcional)
        if (!empty($row['valor_unitario'])) {
            $c = preg_replace('/[^\d,.\-]/', '', $row['valor_unitario']);
            if (str_contains($c, ',')) { $c = str_replace('.', '', $c); $c = str_replace(',', '.', $c); }
            $row['valor_unitario'] = (float) $c;
        } else {
            $row['valor_unitario'] = null;
        }

        // Validade (opcional)
        if (!empty($row['validade'])) {
            $data = null;
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                try { $data = Carbon::createFromFormat($fmt, $row['validade']); if ($data) break; } catch (\Throwable) {}
            }
            if (!$data) return 'validade inválida (use dd/mm/aaaa)';
            $row['validade'] = $data->format('Y-m-d');
        } else {
            $row['validade'] = null;
        }

        // SKU trim
        if (!empty($row['sku'])) $row['sku'] = Str::limit($row['sku'], 100, '');

        return null;
    }
}
