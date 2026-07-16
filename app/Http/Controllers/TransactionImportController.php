<?php

namespace App\Http\Controllers;

use App\Models\FinancialCategory;
use App\Models\Project;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import de planilhas de gastos (CSV) — multi-painel (NGO/Manager/Common).
 *
 * Headers fixos (case-insensitive, acentos ignorados):
 *   descricao (obrigatorio)
 *   valor     (obrigatorio, aceita "R$ 1.234,56" ou "1234.56")
 *   data      (obrigatorio, dd/mm/aaaa ou aaaa-mm-dd)
 *   tipo      (obrigatorio, receita|despesa|income|expense)
 *   categoria (opcional, find-or-create)
 *   projeto   (opcional, busca por nome exato no tenant)
 *
 * Fluxo: showForm → preview (post CSV) → import (post confirm).
 * Idempotencia por (tenant, description, date, amount) — evita duplicar.
 * Status: 'pending' (aguarda aprovacao manual do gestor).
 */
class TransactionImportController extends Controller
{
    private const MAX_ROWS = 5000;

    private const HEADER_ALIASES = [
        'descricao' => ['descricao', 'descrição', 'description', 'desc', 'historico', 'histórico'],
        'valor'     => ['valor', 'amount', 'value', 'preco', 'preço'],
        'data'      => ['data', 'date', 'vencimento'],
        'tipo'      => ['tipo', 'type', 'natureza'],
        'categoria' => ['categoria', 'category', 'grupo'],
        'projeto'   => ['projeto', 'project', 'obra'],
    ];

    public function showForm(): View
    {
        return view('finance.import.form');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $csv = "descricao,valor,data,tipo,categoria,projeto\n"
             . "\"Aluguel escritório\",\"R\$ 1.500,00\",\"01/07/2026\",despesa,\"Aluguel\",\n"
             . "\"Salário Ana\",\"3200.00\",\"05/07/2026\",despesa,\"Folha de pagamento\",\"Projeto Musica\"\n"
             . "\"Doação empresa X\",\"500,00\",\"10/07/2026\",receita,\"Doações\",\n";

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv; // UTF-8 BOM pra Excel abrir corretamente
        }, 'template-planilha-vivensi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5 MB
        ]);

        $parsed = $this->parseCsv($request->file('file')->getRealPath());

        if (isset($parsed['error'])) {
            return back()->with('error', $parsed['error']);
        }

        // Guardar em session pra confirmar depois (max 5000 linhas)
        session()->put('transaction_import.rows', $parsed['rows']);
        session()->put('transaction_import.errors', $parsed['errors']);

        return view('finance.import.preview', [
            'rows'         => $parsed['rows'],
            'parseErrors'  => $parsed['errors'],
            'summary'      => [
                'total'  => count($parsed['rows']),
                'valid'  => count(array_filter($parsed['rows'], fn ($r) => empty($r['_error']))),
                'errors' => count($parsed['errors']),
            ],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $rows = session()->pull('transaction_import.rows', []);
        session()->forget('transaction_import.errors');

        if (empty($rows)) {
            return redirect()->route('finance.import.form')
                ->with('error', 'Sessão expirou. Faça upload novamente.');
        }

        $tenantId = auth()->user()->tenant_id;
        $stats    = ['created' => 0, 'duplicated' => 0, 'errors' => 0];

        DB::transaction(function () use ($rows, $tenantId, &$stats) {
            foreach ($rows as $row) {
                if (!empty($row['_error'])) {
                    $stats['errors']++;
                    continue;
                }

                // Deduplicacao por (tenant, description, date, amount) — memoria user
                $exists = Transaction::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('description', $row['descricao'])
                    ->whereDate('date', $row['data'])
                    ->where('amount', $row['valor'])
                    ->exists();

                if ($exists) {
                    $stats['duplicated']++;
                    continue;
                }

                $categoryId = $this->resolveCategoryId($tenantId, $row['categoria'] ?? null, $row['tipo']);
                $projectId  = $this->resolveProjectId($tenantId, $row['projeto'] ?? null);

                Transaction::create([
                    'tenant_id'   => $tenantId,
                    'project_id'  => $projectId,
                    'description' => $row['descricao'],
                    'amount'      => $row['valor'],
                    'date'        => $row['data'],
                    'type'        => $row['tipo'],
                    'category_id' => $categoryId,
                    'status'      => 'pending', // memoria user — importados vao pra aprovacao
                ]);

                $stats['created']++;
            }
        });

        Log::info('TRANSACTION_IMPORT_COMPLETED', array_merge($stats, [
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
        ]));

        return redirect()->route('finance.import.form')->with('success', sprintf(
            '%d transações criadas · %d duplicadas ignoradas · %d com erro.',
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

        // Skip BOM se existir
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
        $missing   = array_diff(['descricao', 'valor', 'data', 'tipo'], array_keys($headerMap));
        if (!empty($missing)) {
            fclose($handle);
            return ['error' => 'Faltam colunas obrigatórias: ' . implode(', ', $missing)];
        }

        $rows     = [];
        $errors   = [];
        $rowNum   = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count(array_filter($cells, fn ($c) => $c !== '')) === 0) {
                continue; // linha vazia
            }
            if (count($rows) >= self::MAX_ROWS) {
                $errors[] = "Limite de " . self::MAX_ROWS . " linhas atingido — resto ignorado.";
                break;
            }

            $row = ['_line' => $rowNum, '_error' => null];
            foreach ($headerMap as $canonical => $colIdx) {
                $row[$canonical] = trim($cells[$colIdx] ?? '');
            }

            $err = $this->validateAndNormalize($row);
            if ($err) {
                $row['_error']   = $err;
                $errors[]        = "Linha $rowNum: $err";
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
        // Remove acentos
        $s = strtr($s, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        return $s;
    }

    private function validateAndNormalize(array &$row): ?string
    {
        // Descricao
        if (empty($row['descricao'])) {
            return 'descricao vazia';
        }
        if (mb_strlen($row['descricao']) > 500) {
            return 'descricao > 500 chars';
        }

        // Valor: aceita "R$ 1.234,56", "1234.56", "1234,56"
        $rawValor = $row['valor'] ?? '';
        $clean    = preg_replace('/[^\d,.\-]/', '', $rawValor);
        // Se tem virgula, formato BR (troca . por nada e , por .)
        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        $valor = (float) $clean;
        if ($valor <= 0) {
            return 'valor inválido';
        }
        $row['valor'] = $valor;

        // Data: dd/mm/aaaa ou aaaa-mm-dd
        $rawData = $row['data'] ?? '';
        $data    = null;
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y'] as $fmt) {
            try {
                $data = Carbon::createFromFormat($fmt, $rawData);
                if ($data) break;
            } catch (\Throwable) {}
        }
        if (!$data) {
            return 'data inválida (use dd/mm/aaaa)';
        }
        $row['data'] = $data->format('Y-m-d');

        // Tipo
        $tipo = $this->normalize($row['tipo'] ?? '');
        $map  = [
            'receita' => 'income', 'entrada' => 'income', 'income' => 'income',
            'despesa' => 'expense', 'saida' => 'expense', 'expense' => 'expense',
        ];
        if (!isset($map[$tipo])) {
            return 'tipo inválido (use receita ou despesa)';
        }
        $row['tipo'] = $map[$tipo];

        return null;
    }

    // ── Resolucao de FKs ────────────────────────────────────────────────────

    private function resolveCategoryId(int $tenantId, ?string $name, string $type): ?int
    {
        if (empty($name)) return null;
        $name = Str::limit(trim($name), 100, '');

        $cat = FinancialCategory::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if ($cat) return $cat->id;

        return FinancialCategory::create([
            'tenant_id' => $tenantId,
            'name'      => $name,
            'type'      => $type,
        ])->id;
    }

    private function resolveProjectId(int $tenantId, ?string $name): ?int
    {
        if (empty($name)) return null;
        $name = trim($name);

        $project = Project::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $project?->id;
    }
}
