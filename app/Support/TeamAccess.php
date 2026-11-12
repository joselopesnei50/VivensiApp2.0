<?php

namespace App\Support;

use App\Models\User;

/**
 * Hierarquia da equipe dentro de uma ONG (tenant type = ngo).
 *
 *  - Administrador (role ngo): tudo.
 *  - Coordenação (role manager): a operação inteira (financeiro, projetos,
 *    captação, comunicação), sem equipe, RH, configurações, plano e API.
 *  - Colaborador (role employee): o dia a dia do atendimento (beneficiários,
 *    turmas e frequência, atendimentos, agenda, documentos, Academy), sem
 *    financeiro, captação, comunicação, configurações e API.
 *
 * Uma lista só, usada pelo middleware (bloqueia o acesso) e pelo menu
 * (esconde o link) — assim o que aparece é exatamente o que abre.
 */
class TeamAccess
{
    public const ROLE_LABELS = [
        'ngo'      => 'Administrador',
        'manager'  => 'Coordenação',
        'employee' => 'Colaborador',
    ];

    public const ROLE_DESCRIPTIONS = [
        'ngo'      => 'Acesso total: equipe, configurações, plano, API e todos os módulos.',
        'manager'  => 'Toda a operação: financeiro, projetos, captação e comunicação. Sem equipe, RH, configurações, plano e API.',
        'employee' => 'Dia a dia do atendimento: beneficiários, turmas e frequência, atendimentos, agenda, documentos e Academy.',
    ];

    /** Áreas que a Coordenação não abre (prefixos de URL). */
    private const BLOCKED_COORDINATOR = [
        'ngo/team', 'ngo/hr', 'manager/team',
        'settings', 'minha-conta', 'ngo/conformidade/configurar',
        // portal público e SIC respondem pela organização: ficam com o administrador
        'ngo/transparencia', 'ngo/transparency', 'ngo/sic',
    ];

    /** Áreas que o Colaborador não abre (prefixos de URL), além das da Coordenação. */
    private const BLOCKED_EMPLOYEE = [
        // financeiro
        'transactions', 'finance', 'ngo/budget', 'ngo/reconciliation', 'manager/reconciliation', 'ngo/reports/dre',
        // captação
        'ngo/donors', 'ngo/receipts', 'ngo/grants', 'ngo/sponsorships', 'ngo/radar', 'ngo/campaigns', 'raffles',
        // comunicação e marketing
        'whatsapp', 'ngo/email-campaigns', 'manager/email-campaigns', 'ngo/landing-pages', 'manager/landing-pages',
        'marketing', 'social-ai', 'social', 'prospecting', 'banners', 'intelligence',
        // gestão e governança
        'ngo/assets', 'ngo/inventory', 'ngo/contracts', 'manager/contracts', 'ngo/audit', 'ngo/transparencia',
        'ngo/transparency', 'ngo/sic', 'ngo/conformidade', 'smart-analysis', 'ngo/smart-analysis', 'strategy-room',
        'ngo/beneficiaries/insights',
    ];

    public static function label(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ($role === 'credenciado' ? 'Credenciado' : 'Colaborador');
    }

    /** A regra só vale para membros de ONG; outros tipos de conta seguem como antes. */
    public static function appliesTo(?User $user): bool
    {
        return $user
            && in_array($user->role, ['manager', 'employee'], true)
            && optional($user->tenant)->type === 'ngo';
    }

    public static function allowsPath(?User $user, string $path): bool
    {
        if (!self::appliesTo($user)) {
            return true;
        }

        $path = trim(strtolower(parse_url($path, PHP_URL_PATH) ?? $path), '/');
        $blocked = $user->role === 'employee'
            ? array_merge(self::BLOCKED_COORDINATOR, self::BLOCKED_EMPLOYEE)
            : self::BLOCKED_COORDINATOR;

        foreach ($blocked as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }
        return true;
    }

    /** Para o menu: recebe a URL completa do link. */
    public static function canSee(string $url): bool
    {
        return self::allowsPath(auth()->user(), $url);
    }
}
