<?php

/*
| O painel usa o Font Awesome 6.4 gratuito (cdnjs). Ícones que só existem na
| versão paga aparecem como um quadrado vazio. Estes foram trocados em 19/11/2026
| e não devem voltar.
*/

it('não usa ícones da versão paga do Font Awesome', function () {
    $pro = ['shield-check', 'shield-xmark', 'shield-plus', 'shield-exclamation', 'sparkles', 'calendar-star', 'webhook',
        'calendar-import', 'hand-wave', 'user-headset', 'hands-holding-heart', 'chart-project', 'calendar-clock', 'calendar-range',
        'file-check', 'trending-up', 'trending-down', 'party-horn', 'arrows-up-down-to-line', 'chevron-double-up', 'chevron-double-down', 'grid-2', 'x-twitter'];
    $root = dirname(__DIR__, 2);
    $found = [];
    foreach (['resources/views', 'app'] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS)) as $f) {
            $path = (string) $f;
            if (!str_ends_with($path, '.php')) continue;
            $src = file_get_contents($path);
            foreach ($pro as $icon) {
                if (preg_match('/(?<![\w-])fa-' . preg_quote($icon, '/') . '(?![\w-])/', $src)) {
                    $found[] = str_replace($root . '/', '', $path) . ': fa-' . $icon;
                }
            }
        }
    }
    expect(array_unique($found))->toBe([]);
});
