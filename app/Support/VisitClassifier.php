<?php

namespace App\Support;

/**
 * Classifica uma página visitada para o Analytics do super admin:
 * área (site público, app dos clientes, admin), módulo do produto e
 * caminho normalizado (projects/12/edit → projects/{id}/edit).
 */
class VisitClassifier
{
    public const AREAS = ['public' => 'Site público', 'app' => 'App dos clientes', 'admin' => 'Admin'];

    public const MODULES = [
        'dashboard'      => 'Painel',
        'projects'       => 'Projetos',
        'tasks'          => 'Tarefas e Kanban',
        'finance'        => 'Financeiro',
        'donors'         => 'Doadores e doações',
        'beneficiaries'  => 'Beneficiários',
        'compliance'     => 'Conformidade e auditoria',
        'team'           => 'Equipe e RH',
        'grants'         => 'Editais e captação',
        'marketing'      => 'Marketing e campanhas',
        'whatsapp'       => 'WhatsApp',
        'assets'         => 'Patrimônio e estoque',
        'receipts'       => 'Recibos',
        'transparency'   => 'Transparência',
        'reports'        => 'Relatórios e IA',
        'contracts'      => 'Contratos',
        'raffles'        => 'Rifas',
        'sponsorships'   => 'Apadrinhamento',
        'personal'       => 'Gestão pessoal',
        'academy'        => 'Academy',
        'settings'       => 'Configurações e conta',
        'support'        => 'Suporte',
        'other'          => 'Outros',
    ];

    /** Primeiro segmento do caminho → módulo. */
    private const FIRST = [
        'dashboard' => 'dashboard', 'projects' => 'projects', 'tasks' => 'tasks', 'kanban' => 'tasks',
        'transactions' => 'finance', 'finance' => 'finance', 'export' => 'finance',
        'whatsapp' => 'whatsapp', 'social' => 'marketing', 'social-ai' => 'marketing', 'marketing' => 'marketing',
        'email-campaigns' => 'marketing', 'prospecting' => 'marketing',
        'reports' => 'reports', 'smart-analysis' => 'reports', 'intelligence' => 'reports', 'strategy-room' => 'reports', 'search' => 'reports',
        'raffles' => 'raffles', 'academy' => 'academy', 'class-sessions' => 'academy',
        'settings' => 'settings', 'profile' => 'settings', 'minha-conta' => 'settings', 'notifications' => 'settings', '2fa' => 'settings',
        'support' => 'support', 'help' => 'support', 'attachments' => 'other', 'transparencia' => 'transparency',
    ];

    /** ngo/xxx, manager/xxx, personal/xxx → módulo pelo segundo segmento. */
    private const SECOND = [
        'beneficiaries' => 'beneficiaries', 'conformidade' => 'compliance', 'audit' => 'compliance', 'sic' => 'compliance',
        'hr' => 'team', 'team' => 'team', 'perfil-operacional' => 'team', 'approvals' => 'team', 'schedule' => 'team',
        'email-campaigns' => 'marketing', 'campaigns' => 'marketing', 'landing-pages' => 'marketing',
        'assets' => 'assets', 'inventory' => 'assets',
        'grants' => 'grants', 'radar' => 'grants',
        'budget' => 'finance', 'reconciliation' => 'finance', 'dre' => 'finance',
        'donors' => 'donors', 'receipts' => 'receipts', 'reports' => 'reports', 'smart-analysis' => 'reports',
        'contracts' => 'contracts', 'sponsorships' => 'sponsorships', 'transparencia' => 'transparency', 'transparency' => 'transparency',
        'kanban' => 'tasks',
        'quotes' => 'personal', 'agenda' => 'personal', 'catalog' => 'personal', 'clients' => 'personal',
    ];

    /** Caminhos que nunca são "página vista" (atualizações automáticas, infraestrutura). */
    private const NOISE = '~^(api/|_|livewire/|horizon|broadcasting|sanctum/|chat/|health|ping|robots\.txt|sitemap|lp-sitemap|admin/api/|.*/poll$)~';

    public static function isNoise(string $path): bool
    {
        return (bool) preg_match(self::NOISE, ltrim($path, '/'));
    }

    public static function area(string $path, ?int $userId, bool $isSuperAdmin = false): string
    {
        $path = ltrim($path, '/');
        if ($isSuperAdmin || str_starts_with($path, 'admin')) {
            return 'admin';
        }
        return $userId ? 'app' : 'public';
    }

    public static function module(string $path): string
    {
        $seg = explode('/', ltrim($path, '/'));
        $first = $seg[0] ?? '';
        if (in_array($first, ['ngo', 'manager', 'personal'], true)) {
            $second = $seg[1] ?? '';
            if ($second === '' || $second === 'dashboard') {
                return 'dashboard';
            }
            return self::SECOND[$second] ?? ($first === 'personal' ? 'personal' : 'other');
        }
        if ($first === '') {
            return 'dashboard';
        }
        return self::FIRST[$first] ?? 'other';
    }

    public static function normalize(string $path): string
    {
        $parts = array_map(function ($s) {
            if ($s === '') return $s;
            if (ctype_digit($s)) return '{id}';
            if (preg_match('~^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$~i', $s)) return '{uuid}';
            if (strlen($s) >= 20 && preg_match('~^[A-Za-z0-9_\-]+$~', $s) && preg_match('~\d~', $s)) return '{token}';
            return $s;
        }, explode('/', trim($path, '/')));
        $out = implode('/', $parts);
        return $out === '' ? '/' : mb_substr($out, 0, 500);
    }

    public static function moduleLabel(?string $key): string
    {
        return self::MODULES[$key] ?? self::MODULES['other'];
    }
}
