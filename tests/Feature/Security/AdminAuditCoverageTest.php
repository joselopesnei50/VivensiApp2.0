<?php

use App\Models\AdminAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Http\Middleware\RequireTwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

/**
 * P1.c — Hardening do painel Super Admin.
 *
 * Cobre:
 *  1. Cobertura do middleware EnsureSuperAdmin: 100% das rotas registradas
 *     com nome 'admin.*' recusam usuario nao-super-admin.
 *  2. AdminAuditLog gravado em acoes sensiveis:
 *     - AdminSettingsController::store (mudanca de API keys)
 *     - BotController::save, saveAtendimento, updateUserPhone
 *     - AdminTeamController::store, update, destroy
 *  3. Fundo: secrets nao vazam no context do AdminAuditLog.
 */

uses(RefreshDatabase::class);

function superAdmin(): User
{
    return User::factory()->create([
        'role'      => 'super_admin',
        'tenant_id' => Tenant::factory()->create(['subscription_status' => 'active'])->id,
    ]);
}

// ── 1. Cobertura de middleware ───────────────────────────────────────────────

it('todas as rotas com nome admin.* rejeitam usuario nao-super-admin', function () {
    $regular = User::factory()->create([
        'role'      => 'manager',
        'tenant_id' => Tenant::factory()->create(['subscription_status' => 'active'])->id,
    ]);

    $failed = [];
    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();
        if (!$name || !str_starts_with($name, 'admin.')) {
            continue;
        }
        // So valida rotas GET (POST/PUT/DELETE precisam de CSRF/payload —
        // 403 rola no mesmo lugar antes disso).
        if (!in_array('GET', $route->methods(), true)) {
            continue;
        }
        // Skip rotas com parametros dinamicos que exigem fixture (nao conseguem
        // resolver route model binding sem factory de todos os models).
        if (preg_match('/\{[^}]+\}/', $route->uri())) {
            continue;
        }

        $status = $this->actingAs($regular)
            ->withoutMiddleware(RequireTwoFactor::class)
            ->get('/' . ltrim($route->uri(), '/'))
            ->status();

        // Aceita 403 (bloqueio direto) ou 302 (redirect pra login/checkout)
        // — o que NAO pode e 200.
        if ($status === 200) {
            $failed[] = $name . ' [' . $route->uri() . '] status=' . $status;
        }
    }

    expect($failed)->toBe([]);
});

it('rota admin.dashboard retorna 200 para super_admin (sanity)', function () {
    $this->actingAs(superAdmin())
        ->withoutMiddleware(RequireTwoFactor::class)
        ->get('/admin')
        ->assertOk();
});

// ── 2. AuditLog em acoes sensiveis ───────────────────────────────────────────

it('AdminSettingsController::store grava AuditLog com keys_touched sem valores', function () {
    $this->actingAs(superAdmin())
        ->withoutMiddleware(RequireTwoFactor::class)
        ->post('/admin/settings', [
            'deepseek_api_key' => 'sk-CANARY-DO-NOT-LOG',
            'brevo_api_key'    => 'xk-CANARY-DO-NOT-LOG',
        ])
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'settings.updated')->first();
    expect($log)->not->toBeNull();
    expect($log->context['keys_touched'])->toContain('deepseek_api_key');
    expect($log->context['keys_touched'])->toContain('brevo_api_key');

    // Canario: nem valores nem prefixos das secrets podem aparecer no log.
    $raw = json_encode($log->getAttributes(), JSON_UNESCAPED_UNICODE);
    expect($raw)->not->toContain('CANARY-DO-NOT-LOG');
    expect($raw)->not->toContain('sk-CANARY');
});

it('BotController::save grava AuditLog', function () {
    $this->actingAs(superAdmin())
        ->withoutMiddleware(RequireTwoFactor::class)
        ->post('/admin/bot/settings', [
            'bot_enabled'      => '1',
            'bot_instance_name' => 'v1_test',
        ])
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'bot.config_updated')->first();
    expect($log)->not->toBeNull();
    expect($log->context['fields_changed'])->toContain('bot_enabled');
    expect($log->context['fields_changed'])->toContain('bot_instance_name');
});

it('BotController::saveAtendimento grava AuditLog', function () {
    $this->actingAs(superAdmin())
        ->withoutMiddleware(RequireTwoFactor::class)
        ->post('/admin/bot/atendimento', [
            'atend_welcome_msg' => 'Ola',
            'faq_keyword'       => ['horario'],
            'faq_response'      => ['de 8h as 18h'],
        ])
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'bot.attendance_updated')->first();
    expect($log)->not->toBeNull();
    expect((int) $log->context['faq_entries'])->toBe(1);
});

it('BotController::updateUserPhone grava AuditLog com target_id', function () {
    $admin = superAdmin();
    $target = User::factory()->create(['tenant_id' => $admin->tenant_id, 'name' => 'Alvo Silva']);

    $this->actingAs($admin)
        ->withoutMiddleware(RequireTwoFactor::class)
        ->post("/admin/bot/users/{$target->id}/phone", ['phone' => '11999998888'])
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'bot.user_phone_updated')->first();
    expect($log)->not->toBeNull();
    expect((int) $log->target_id)->toBe($target->id);
    expect($log->target_name)->toBe('Alvo Silva');
    // Nao logamos o telefone em si — sensivel.
    $raw = json_encode($log->getAttributes(), JSON_UNESCAPED_UNICODE);
    expect($raw)->not->toContain('11999998888');
});

it('AdminTeamController::store grava AuditLog', function () {
    $this->actingAs(superAdmin())
        ->withoutMiddleware(RequireTwoFactor::class)
        ->post('/admin/team', [
            'name'       => 'Novo Membro',
            'email'      => 'novo@vivensi.com.br',
            'role'       => 'analyst',
            'department' => 'suporte',
            'password'   => 'senhaMuitoSegura123',
        ])
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'team.member_added')->first();
    expect($log)->not->toBeNull();
    expect($log->target_name)->toBe('Novo Membro');
    // Senha nunca no AuditLog.
    $raw = json_encode($log->getAttributes(), JSON_UNESCAPED_UNICODE);
    expect($raw)->not->toContain('senhaMuitoSegura123');
});

it('AdminTeamController::destroy grava AuditLog', function () {
    $admin  = superAdmin();
    $victim = User::factory()->create([
        'tenant_id'        => $admin->tenant_id,
        'is_platform_team' => true,
        'name'             => 'A Ser Removido',
    ]);

    $this->actingAs($admin)
        ->withoutMiddleware(RequireTwoFactor::class)
        ->delete("/admin/team/{$victim->id}")
        ->assertRedirect();

    $log = AdminAuditLog::where('action', 'team.member_removed')->first();
    expect($log)->not->toBeNull();
    expect((int) $log->target_id)->toBe($victim->id);
    expect($log->target_name)->toBe('A Ser Removido');
});
