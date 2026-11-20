<?php

namespace App\Support;

/**
 * Converte o markdown do plano de marketing (Markmap) em blocos para a aba
 * "Plano Executivo": cada "##" vira um card, cada "###" um sub-tópico com
 * os seus itens.
 *
 * A versão anterior (na view) guardava os sub-tópicos por referência PHP e,
 * a cada novo "###", sobrescrevia todos os anteriores: o plano inteiro
 * aparecia com o mesmo sub-tópico repetido.
 */
class MarketingPlanOutline
{
    /** @return array{root: ?string, sections: array<int, array{title: string, h3s: array<int, array{title: string, bullets: array<int, string>}>}>} */
    public static function parse(string $markdown): array
    {
        $root = null;
        $sections = [];
        $s = -1; // índice do "##" atual
        $h = -1; // índice do "###" atual dentro dele

        foreach (preg_split("/\r?\n/", $markdown) as $line) {
            $trimmed = rtrim($line);
            if (trim($trimmed) === '') {
                continue;
            }

            if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                $root ??= trim($m[1]);
            } elseif (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                $sections[] = ['title' => trim($m[1]), 'h3s' => []];
                $s = count($sections) - 1;
                $h = -1;
            } elseif (preg_match('/^###\s+(.+)$/', $trimmed, $m) && $s >= 0) {
                $sections[$s]['h3s'][] = ['title' => trim($m[1]), 'bullets' => []];
                $h = count($sections[$s]['h3s']) - 1;
            } elseif ($s >= 0 && (preg_match('/^\s*[-*+]\s+(.+)$/', $trimmed, $m) || preg_match('/^#{4,}\s+(.+)$/', $trimmed, $m))) {
                if ($h < 0) {
                    // item direto no "##", antes de qualquer "###"
                    $sections[$s]['h3s'][] = ['title' => 'Pontos-chave', 'bullets' => []];
                    $h = count($sections[$s]['h3s']) - 1;
                }
                $sections[$s]['h3s'][$h]['bullets'][] = trim($m[1]);
            }
        }

        return ['root' => $root, 'sections' => $sections];
    }
}
