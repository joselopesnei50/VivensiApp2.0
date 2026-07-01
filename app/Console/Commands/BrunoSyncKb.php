<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Sincroniza a knowledge base do Bruno (product.panels em config/bot-vendedor.php)
 * a partir do menu real em resources/views/layouts/app.blade.php.
 *
 * Sem flag: dry-run — mostra diff e sai.
 * Com --write: reescreve o bloco 'panels' preservando o resto do config.
 *
 * Fonte da verdade: o menu. Se um item some do menu, some da KB.
 * Descrições enriquecidas do config (ex: "Doadores (CRM completo)") são
 * preservadas quando o "core" do nome bate com o item do menu.
 */
class BrunoSyncKb extends Command
{
    protected $signature   = 'bruno:sync-kb {--write : Grava mudanças em config/bot-vendedor.php}';
    protected $description = 'Sincroniza os painéis da KB do Bruno com o menu real (resources/views/layouts/app.blade.php)';

    private const ROLE_TO_KEY = [
        'manager' => 'gestor',
        'ngo'     => 'terceiro_setor',
        'common'  => 'mei',
    ];

    public function handle(): int
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $configPath = config_path('bot-vendedor.php');

        if (!is_file($layoutPath)) {
            $this->error("Layout não encontrado: {$layoutPath}");
            return 1;
        }
        if (!is_file($configPath)) {
            $this->error("Config não encontrado: {$configPath}");
            return 1;
        }

        $layout    = file_get_contents($layoutPath);
        $extracted = $this->extractPanels($layout);

        if (empty($extracted)) {
            $this->error('Nenhum painel foi extraído. O menu mudou de estrutura? Confira os marcadores em ' . basename($layoutPath));
            return 1;
        }

        $current = config('bot-vendedor.product.panels', []);
        $merged  = $this->mergePreservingDescriptions($extracted, $current);

        $this->printDiff($current, $merged);

        if (!$this->option('write')) {
            $this->line('');
            $this->comment('Dry-run — nada foi escrito. Rode com --write pra aplicar.');
            return 0;
        }

        try {
            $this->rewriteConfig($configPath, $merged);
        } catch (\Throwable $e) {
            $this->error("Falhou ao reescrever config: {$e->getMessage()}");
            return 1;
        }

        $this->info('OK — config/bot-vendedor.php atualizado.');
        $this->line('Não esqueça: <fg=yellow>php artisan config:clear</> no VPS após deploy.');
        return 0;
    }

    /**
     * Percorre o layout e devolve estrutura no formato:
     *   ['terceiro_setor' => ['grupos' => ['Grupo A' => ['item 1', ...]]], 'mei' => ..., 'gestor' => ...]
     */
    private function extractPanels(string $layout): array
    {
        $sections = $this->splitByRole($layout);
        $panels   = [];

        foreach ($sections as $role => $chunk) {
            $key    = self::ROLE_TO_KEY[$role] ?? null;
            if (!$key) {
                continue;
            }
            $grupos = $this->extractGroups($chunk);
            if (!empty($grupos)) {
                $panels[$key] = ['grupos' => $grupos];
            }
        }

        return $panels;
    }

    /**
     * Divide o layout em 3 blocos por role (manager, ngo, common).
     * Marcadores conhecidos do arquivo:
     *   @elseif (auth()->user()->role == 'manager')
     *   @elseif (auth()->user()->role == 'ngo'
     *   @else  ← seguido do comentário "Menu Comum / MEI / Empresa"
     *   @endif ← fecha o menu MEI
     */
    private function splitByRole(string $layout): array
    {
        $out = ['manager' => '', 'ngo' => '', 'common' => ''];

        $mgrPos = strpos($layout, "@elseif (auth()->user()->role == 'manager')");
        $ngoPos = strpos($layout, "@elseif (auth()->user()->role == 'ngo'");
        $meiPos = strpos($layout, 'Menu Comum / MEI / Empresa');
        if ($mgrPos === false || $ngoPos === false || $meiPos === false) {
            return $out;
        }

        // Fim do bloco MEI: primeiro @endif após meiPos.
        $endPos = strpos($layout, '@endif', $meiPos);
        if ($endPos === false) {
            $endPos = strlen($layout);
        }

        $out['manager'] = substr($layout, $mgrPos, $ngoPos - $mgrPos);
        $out['ngo']     = substr($layout, $ngoPos, $meiPos - $ngoPos);
        $out['common']  = substr($layout, $meiPos, $endPos - $meiPos);

        return $out;
    }

    /**
     * Dentro de um bloco de role, extrai cada grupo:
     *   'Nome do Grupo' => ['item 1', 'item 2', ...]
     */
    private function extractGroups(string $chunk): array
    {
        // Regex captura header + tudo até o próximo header ou fim do bloco.
        // Estrutura:
        //   <div class="menu-group-header ..."> <i ...></i> NOME <i class="fas fa-chevron-down ...
        //   ...items...
        //   (próximo header ou fim)
        $pattern = '/<div class="menu-group-header[^"]*"[^>]*>\s*'
                 . '<i[^>]*class="[^"]*group-icon[^"]*"[^>]*><\/i>\s*'
                 . '(?P<name>.+?)\s*'
                 . '<i[^>]*fa-chevron-down/su';

        if (!preg_match_all($pattern, $chunk, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $count  = count($matches[0]);
        $groups = [];

        for ($i = 0; $i < $count; $i++) {
            $rawName    = $matches['name'][$i][0];
            $groupName  = $this->cleanText($rawName);
            $startOfset = $matches[0][$i][1];
            $endOffset  = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($chunk);
            $inside     = substr($chunk, $startOfset, $endOffset - $startOfset);

            $items = $this->extractItems($inside);
            if (!empty($items) && $groupName !== '') {
                // Se um grupo com o mesmo nome já existe (ex: WhatsApp em várias roles),
                // mescla mantendo ordem original + itens novos.
                if (isset($groups[$groupName])) {
                    $groups[$groupName] = array_values(array_unique(array_merge($groups[$groupName], $items)));
                } else {
                    $groups[$groupName] = $items;
                }
            }
        }

        return $groups;
    }

    /**
     * Extrai o texto de cada <li><a>...</a></li> dentro de um bloco de grupo.
     * Descarta itens "Novo ...", "Cadastro rápido ...", "Nova ..." e similares
     * — são shortcuts de UI, não features de KB.
     */
    private function extractItems(string $blockHtml): array
    {
        // Match <li ...><a ...>...</a></li>. Cuidado: o <a> tem class="{{ request()->is(...) }}"
        // com "->" (contém ">"), então usamos .*?"> pra achar o fim da tag de abertura sem parar
        // no primeiro ">".
        if (!preg_match_all('/<li\b[^>]*>\s*<a\b.*?">(?P<inner>.+?)<\/a>\s*<\/li>/su', $blockHtml, $m)) {
            return [];
        }

        $items = [];
        foreach ($m['inner'] as $inner) {
            $text = $this->cleanText($inner);
            if ($text === '' || $this->isShortcutItem($text)) {
                continue;
            }
            if (!in_array($text, $items, true)) {
                $items[] = $text;
            }
        }

        return $items;
    }

    /**
     * "Novo Cliente", "Nova Transação", "Emitir Recibo", "Cadastro rápido X"
     * são atalhos de UI (botão "criar"), não features. Não vão pra KB.
     */
    private function isShortcutItem(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach (['novo ', 'nova ', 'cadastro rápido', 'cadastro rapido', 'cadastrar '] as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }
        // "Emitir Recibo" — atalho pra criar, mas "Recibos & NFS-e" já cobre o feature.
        if (str_starts_with($lower, 'emitir ')) {
            return true;
        }
        return false;
    }

    /**
     * Remove tags, entidades HTML e espaços redundantes.
     * Preserva acentos e o "&" original (após decodificar &amp;).
     */
    private function cleanText(string $raw): string
    {
        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Merge que preserva descrições enriquecidas do config atual quando o "core"
     * do nome bate. Ex: menu diz "Doadores", config diz "Doadores (CRM completo)"
     * → mantém a versão do config.
     *
     * Também preserva 'titulo' do config (curadoria editorial).
     */
    private function mergePreservingDescriptions(array $extracted, array $current): array
    {
        $out = [];
        foreach ($extracted as $panelKey => $panelData) {
            $titulo = $current[$panelKey]['titulo'] ?? $this->defaultTitulo($panelKey);
            $out[$panelKey] = ['titulo' => $titulo, 'grupos' => []];

            $currentGroups = $current[$panelKey]['grupos'] ?? [];

            foreach ($panelData['grupos'] as $groupName => $items) {
                // Preserva phrasing do config quando o grupo já existe (ex: "e" no lugar de "&").
                $existingKey  = $this->findMatchingGroupKey($currentGroups, $groupName);
                $keyToUse     = $existingKey ?? $groupName;
                $currentItems = $existingKey ? $currentGroups[$existingKey] : [];

                $merged = [];
                foreach ($items as $item) {
                    $merged[] = $this->findEnrichedName($item, $currentItems) ?? $item;
                }
                $out[$panelKey]['grupos'][$keyToUse] = $merged;
            }
        }
        return $out;
    }

    private function findMatchingGroupKey(array $currentGroups, string $newName): ?string
    {
        if (isset($currentGroups[$newName])) {
            return $newName;
        }
        $norm = $this->normalize($newName);
        foreach ($currentGroups as $existing => $_) {
            if ($this->normalize($existing) === $norm) {
                return $existing;
            }
        }
        return null;
    }

    private function defaultTitulo(string $panelKey): string
    {
        return match ($panelKey) {
            'terceiro_setor' => 'Painel Terceiro Setor (ONGs/OSCs)',
            'mei'            => 'Painel Pequeno Negócio (MEI, autônomo, PJ Simples)',
            'gestor'         => 'Painel Gestor de Projetos / PME',
            default          => 'Painel',
        };
    }

    /**
     * Encontra o array de itens de um grupo tolerando pequenas diferenças no nome
     * (ex: "Projetos & Captação" vs "Projetos e Captação").
     */
    private function findGroupItems(array $currentGroups, string $groupName): array
    {
        if (isset($currentGroups[$groupName])) {
            return $currentGroups[$groupName];
        }
        $normNew = $this->normalize($groupName);
        foreach ($currentGroups as $existing => $items) {
            if ($this->normalize($existing) === $normNew) {
                return $items;
            }
        }
        return [];
    }

    /**
     * Se algum item do config tem o mesmo "core" que o item do menu, devolve
     * a versão enriquecida. Ex: menu="Doadores", config="Doadores (CRM completo)"
     * → devolve "Doadores (CRM completo)".
     */
    private function findEnrichedName(string $item, array $currentItems): ?string
    {
        $core = $this->coreName($item);
        foreach ($currentItems as $existing) {
            if ($this->coreName($existing) === $core) {
                return $existing;
            }
        }
        return null;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = strtr($s, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
        $s = preg_replace('/\s*&\s*/u', ' e ', $s);
        $s = preg_replace('/[^a-z0-9 ]/u', ' ', $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * "Doadores (CRM completo)" → "doadores"
     * "Chatbot com IA Bruce — treinavel pelo cliente" → "chatbot com ia bruce"
     */
    private function coreName(string $s): string
    {
        $s = preg_replace('/\s*\(.*?\)\s*/u', ' ', $s);
        $s = preg_replace('/\s*[—–-]\s*.*$/u', ' ', $s);
        return $this->normalize($s);
    }

    private function printDiff(array $current, array $merged): void
    {
        $this->line('<fg=cyan>=== Diff KB (config atual → menu real) ===</>');

        $allKeys = array_unique(array_merge(array_keys($current), array_keys($merged)));
        foreach ($allKeys as $panelKey) {
            $this->line("\n<fg=yellow>[{$panelKey}]</>");
            $curGrupos = $current[$panelKey]['grupos'] ?? [];
            $newGrupos = $merged[$panelKey]['grupos']  ?? [];

            $allGroups = array_unique(array_merge(array_keys($curGrupos), array_keys($newGrupos)));
            foreach ($allGroups as $g) {
                $inCur = isset($curGrupos[$g]);
                $inNew = isset($newGrupos[$g]);

                if ($inNew && !$inCur) {
                    $this->line("  <fg=green>+ Grupo:</> {$g}");
                    foreach ($newGrupos[$g] as $i) {
                        $this->line("      <fg=green>+ {$i}</>");
                    }
                    continue;
                }
                if ($inCur && !$inNew) {
                    $this->line("  <fg=red>- Grupo:</> {$g}");
                    foreach ($curGrupos[$g] as $i) {
                        $this->line("      <fg=red>- {$i}</>");
                    }
                    continue;
                }

                $added   = array_diff($newGrupos[$g], $curGrupos[$g]);
                $removed = array_diff($curGrupos[$g], $newGrupos[$g]);

                if (empty($added) && empty($removed)) {
                    continue;
                }
                $this->line("  <fg=cyan>~ Grupo:</> {$g}");
                foreach ($added as $i) {
                    $this->line("      <fg=green>+ {$i}</>");
                }
                foreach ($removed as $i) {
                    $this->line("      <fg=red>- {$i}</>");
                }
            }
        }
    }

    /**
     * Reescreve o bloco 'panels' em config/bot-vendedor.php preservando o resto do arquivo.
     * Localiza o array por balanceamento de colchetes a partir de "'panels' => [".
     */
    private function rewriteConfig(string $path, array $panels): void
    {
        $content = file_get_contents($path);
        $needle  = "'panels' => [";
        $start   = strpos($content, $needle);
        if ($start === false) {
            throw new \RuntimeException("Não achei \"'panels' => [\" em {$path}");
        }

        // Encontra colchete correspondente contando abre/fecha (respeitando strings simples e duplas).
        $end   = $this->findMatchingBracket($content, $start + strlen($needle) - 1);
        if ($end === null) {
            throw new \RuntimeException('Não consegui encontrar o "]" que fecha o bloco panels.');
        }

        $formatted = $this->formatPanelsPhp($panels, 8);
        $new       = substr($content, 0, $start) . $formatted . substr($content, $end + 1);

        if (file_put_contents($path, $new) === false) {
            throw new \RuntimeException("Falhou ao escrever em {$path}");
        }
    }

    /**
     * Dado o offset de um '[', devolve o offset do ']' que o fecha, respeitando
     * strings simples/duplas e escapes básicos. Retorna null se não fechar.
     */
    private function findMatchingBracket(string $s, int $openPos): ?int
    {
        $len   = strlen($s);
        $depth = 0;
        $q     = null; // quote char corrente ou null

        for ($i = $openPos; $i < $len; $i++) {
            $c = $s[$i];

            if ($q !== null) {
                if ($c === '\\') { $i++; continue; }
                if ($c === $q)   { $q = null; }
                continue;
            }

            if ($c === "'" || $c === '"') { $q = $c; continue; }
            if ($c === '[') { $depth++; continue; }
            if ($c === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return null;
    }

    /**
     * Formata o array panels no estilo do arquivo original:
     *   'panels' => [
     *       'terceiro_setor' => [
     *           'titulo' => '...',
     *           'grupos' => [
     *               'Grupo' => ['item 1', 'item 2'],
     *               ...
     *           ],
     *       ],
     *       ...
     *   ]
     *
     * @param int $indent nº de espaços na linha "'panels' => [" (8 no arquivo atual).
     */
    private function formatPanelsPhp(array $panels, int $indent): string
    {
        $pad = str_repeat(' ', $indent);
        $p1  = $pad . '    '; // painel key
        $p2  = $pad . '        '; // titulo/grupos
        $p3  = $pad . '            '; // grupo key
        $p4  = $pad . '                '; // item (não usado — item vai inline)

        $out = "'panels' => [\n";
        foreach ($panels as $panelKey => $panelData) {
            $out .= "{$p1}'" . $this->esc($panelKey) . "' => [\n";
            $out .= "{$p2}'titulo' => '" . $this->esc($panelData['titulo']) . "',\n";
            $out .= "{$p2}'grupos' => [\n";
            foreach ($panelData['grupos'] as $groupName => $items) {
                $itemsPhp = array_map(fn ($it) => "'" . $this->esc($it) . "'", $items);
                $out .= "{$p3}'" . $this->esc($groupName) . "' => [" . implode(', ', $itemsPhp) . "],\n";
            }
            $out .= "{$p2}],\n";
            $out .= "{$p1}],\n";
        }
        $out .= $pad . ']';
        return $out;
    }

    private function esc(string $s): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $s);
    }
}
