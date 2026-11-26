<?php

use App\Models\GeneratedReport;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerCertificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Auditoria do painel (26/11/2026), itens 5, 6 e E1:
 *  - relatório gerado só abre para quem gerou ou para o administrador;
 *  - token da API respeita pessoa suspensa, conta com pendência e o perfil da equipe;
 *  - validação pública do certificado de voluntário volta a funcionar.
 */
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p2Tenant(array $o = []): Tenant
{
    return Tenant::factory()->create(array_merge(['type' => 'ngo', 'subscription_status' => 'active'], $o));
}

function p2User(Tenant $t, string $role = 'ngo', array $o = []): User
{
    return User::factory()->create(array_merge(['tenant_id' => $t->id, 'role' => $role, 'status' => 'active'], $o));
}

function p2Report(Tenant $t, User $owner): GeneratedReport
{
    Storage::disk('local')->put("reports/r{$owner->id}.pdf", '%PDF-1.4');
    return GeneratedReport::create([
        'tenant_id' => $t->id, 'user_id' => $owner->id, 'type' => 'dre_pdf', 'params' => [],
        'status' => 'done', 'file_path' => "reports/r{$owner->id}.pdf", 'filename' => 'dre.pdf',
    ]);
}

it('colaborador não baixa relatório gerado pelo administrador', function () {
    Storage::fake('local');
    $t = p2Tenant();
    $admin = p2User($t);
    $colab = p2User($t, 'employee');
    $rep = p2Report($t, $admin);

    $this->actingAs($colab)->get("/reports/{$rep->id}/download")->assertNotFound();
    $this->actingAs($colab)->getJson("/reports/{$rep->id}/status")->assertNotFound();
    $this->actingAs($admin)->get("/reports/{$rep->id}/download")->assertOk();
});

it('quem gerou baixa o próprio relatório; administrador baixa o de qualquer um', function () {
    Storage::fake('local');
    $t = p2Tenant();
    $admin = p2User($t);
    $colab = p2User($t, 'employee');
    $rep = p2Report($t, $colab);

    $this->actingAs($colab)->get("/reports/{$rep->id}/download")->assertOk();
    $this->actingAs($admin)->get("/reports/{$rep->id}/download")->assertOk();
    $this->actingAs(p2User(p2Tenant()))->get("/reports/{$rep->id}/download")->assertNotFound();
});

it('API: token de pessoa suspensa não funciona (regra que já existia, mantida)', function () {
    $t = p2Tenant();
    $u = p2User($t);
    $token = $u->createToken('teste')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    $u->forceFill(['status' => 'suspended'])->save();
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
});

it('API: conta suspensa ou cancelada não usa o token', function () {
    foreach (['suspended', 'canceled', 'awaiting_contract'] as $status) {
        $t = p2Tenant(['subscription_status' => $status]);
        $token = p2User($t)->createToken('teste')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(402);
    }
});

it('API: colaborador da ONG não usa token (já existia) e a coordenação segue o perfil', function () {
    $t = p2Tenant();
    p2User($t);
    $colab = p2User($t, 'employee')->createToken('teste')->plainTextToken;
    $this->withToken($colab)->getJson('/api/v1/transactions')->assertUnauthorized();

    $this->app['auth']->forgetGuards();
    $coord = p2User($t, 'manager')->createToken('teste')->plainTextToken;
    $this->withToken($coord)->getJson('/api/v1/me')->assertOk();
});

it('suspender ou remover alguém da equipe apaga os tokens da API', function () {
    $t = p2Tenant();
    $admin = p2User($t);
    $m = p2User($t, 'employee');
    $m->createToken('x');
    $other = p2User($t, 'employee');
    $other->createToken('y');

    $this->actingAs($admin)->put("/ngo/team/{$m->id}", ['name' => $m->name, 'role' => 'employee', 'status' => 'suspended']);
    expect($m->tokens()->count())->toBe(0);

    $this->actingAs($admin)->delete("/ngo/team/{$other->id}");
    expect(\Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $other->id)->count())->toBe(0);
});

it('visitante valida o certificado de voluntário pelo QR', function () {
    $t = p2Tenant();
    $admin = p2User($t);
    $this->actingAs($admin);
    $vol = Volunteer::create(['tenant_id' => $t->id, 'name' => 'Ana (exemplo)', 'email' => 'ana@exemplo.com']);
    $cert = VolunteerCertificate::create([
        'tenant_id' => $t->id, 'volunteer_id' => $vol->id, 'uuid' => (string) Str::uuid(),
        'activity_description' => 'Oficina', 'hours' => 10, 'issued_at' => now(),
    ]);
    auth()->logout();

    $ctrl = app(\App\Http\Controllers\HumanResourcesController::class);
    $code = (fn () => $this->certAuthCode($t->id, $vol->id, $cert->id, $cert->issued_at->timestamp))->call($ctrl);

    $this->get("/validar-certificado/{$cert->uuid}?code={$code}")->assertOk()->assertSee('VÁLIDO');
    $this->get("/validar-certificado/{$cert->uuid}?code=ERRADO123")->assertOk()->assertDontSee('Código confere');
});

it('lista de certificados do RH gera o link de validação pelo uuid', function () {
    $t = p2Tenant();
    $admin = p2User($t);
    $this->actingAs($admin);
    $vol = Volunteer::create(['tenant_id' => $t->id, 'name' => 'Ana (exemplo)', 'email' => 'ana@exemplo.com', 'phone' => '16999990000']);
    $cert = VolunteerCertificate::create([
        'tenant_id' => $t->id, 'volunteer_id' => $vol->id, 'uuid' => (string) Str::uuid(),
        'activity_description' => 'Oficina', 'hours' => 10, 'issued_at' => now(),
    ]);

    $html = $this->get('/ngo/hr/certificates')->assertOk()->getContent();
    expect($html)->toContain(urlencode('/validar-certificado/' . $cert->uuid . '?code='));
    expect($html)->not->toContain(urlencode('/validar-certificado/' . $cert->id . '?code='));
});
