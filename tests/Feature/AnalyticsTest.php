<?php

use App\Models\PageVisit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProductAnalyticsService;
use App\Support\VisitClassifier;
use Illuminate\Support\Facades\DB;

/**
 * Analytics do super admin: coleta limpa (sem robôs, polling, erros),
 * classificação por área/módulo, saúde das organizações e a página.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const AN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

function anVisit(array $attrs): void
{
    DB::table('page_visits')->insert($attrs + ['path' => 'dashboard', 'area' => 'app', 'module' => 'dashboard', 'created_at' => now()]);
}

function anAdmin(): User
{
    $u = User::factory()->create(['role' => 'super_admin', 'two_factor_confirmed_at' => now()]);
    session(['2fa_verified' => true]);
    return $u;
}

it('classifica área, módulo e normaliza caminhos', function () {
    expect(VisitClassifier::normalize('projects/12/edit'))->toBe('projects/{id}/edit')
        ->and(VisitClassifier::normalize('chat/3ef7104c-a02b-4a0f-8815-17a04f9ddeb2/x'))->toBe('chat/{uuid}/x')
        ->and(VisitClassifier::normalize('/'))->toBe('/')
        ->and(VisitClassifier::module('ngo/beneficiaries/5'))->toBe('beneficiaries')
        ->and(VisitClassifier::module('ngo/conformidade'))->toBe('compliance')
        ->and(VisitClassifier::module('transactions/create'))->toBe('finance')
        ->and(VisitClassifier::module('whatsapp/chat'))->toBe('whatsapp')
        ->and(VisitClassifier::module('ngo'))->toBe('dashboard')
        ->and(VisitClassifier::area('admin/analytics', 1, true))->toBe('admin')
        ->and(VisitClassifier::area('projects', 5))->toBe('app')
        ->and(VisitClassifier::area('blog/x', null))->toBe('public')
        ->and(VisitClassifier::isNoise('admin/chat-site/3/poll'))->toBeTrue()
        ->and(VisitClassifier::isNoise('chat/abc/mensagens'))->toBeTrue()
        ->and(VisitClassifier::isNoise('blog'))->toBeFalse();
});

it('registra só páginas vistas de verdade: sem robô, sem JSON/polling, sem erro', function () {
    $this->withHeaders(['User-Agent' => AN_UA, 'Referer' => 'https://www.google.com/'])->get('/termos')->assertOk();
    $v = PageVisit::latest('id')->first();
    expect($v->path)->toBe('termos')->and($v->area)->toBe('public')->and($v->source)->toBe('google')
        ->and($v->device)->toBe('desktop')->and($v->visitor)->toHaveLength(16);

    $before = PageVisit::count();
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/termos');
    $this->withHeaders(['User-Agent' => AN_UA, 'Accept' => 'application/json'])->get('/termos');
    $this->withHeaders(['User-Agent' => AN_UA])->get('/pagina-que-nao-existe-xyz');
    $this->withHeaders(['User-Agent' => AN_UA])->get('/dashboard'); // redireciona para o login
    expect(PageVisit::count())->toBe($before);
});

it('migração reaproveita o histórico: área, módulo e caminho normalizado', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $client = User::factory()->create(['role' => 'ngo']);
    $migration = require database_path('migrations/2026_11_05_170001_add_classification_to_page_visits.php');
    $migration->down();

    DB::table('page_visits')->insert([
        ['tenant_id' => $client->tenant_id, 'user_id' => $client->id, 'path' => 'projects/12/edit', 'created_at' => now()],
        ['tenant_id' => $admin->tenant_id, 'user_id' => $admin->id, 'path' => 'ngo/donors', 'created_at' => now()],
        ['tenant_id' => null, 'user_id' => null, 'path' => 'blog/como-captar', 'created_at' => now()],
        ['tenant_id' => null, 'user_id' => $admin->id, 'path' => 'admin/chat-site/3/poll', 'created_at' => now()],
    ]);

    $migration->up();

    $rows = DB::table('page_visits')->orderBy('id')->get();
    expect($rows)->toHaveCount(3) // polling apagado
        ->and($rows[0]->path)->toBe('projects/{id}/edit')->and($rows[0]->area)->toBe('app')->and($rows[0]->module)->toBe('projects')
        ->and($rows[1]->area)->toBe('admin')
        ->and($rows[2]->area)->toBe('public')->and($rows[2]->module)->toBe('other');
});

it('calcula uso, saúde das organizações e adoção por módulo', function () {
    $this->travelTo(now()->setTime(12, 0));
    $ativa   = Tenant::factory()->create(['name' => 'ONG Ativa', 'subscription_status' => 'active', 'created_at' => now()->subMonths(3)]);
    $parada  = Tenant::factory()->create(['name' => 'ONG Parada', 'subscription_status' => 'active', 'created_at' => now()->subMonths(3)]);
    $nova    = Tenant::factory()->create(['name' => 'ONG Nova', 'subscription_status' => 'trialing', 'created_at' => now()->subDays(5)]);
    $u1 = User::factory()->create(['tenant_id' => $ativa->id, 'role' => 'ngo']);
    $u2 = User::factory()->create(['tenant_id' => $ativa->id, 'role' => 'ngo']);
    $u3 = User::factory()->create(['tenant_id' => $parada->id, 'role' => 'ngo']);

    anVisit(['tenant_id' => $ativa->id, 'user_id' => $u1->id, 'module' => 'projects', 'path' => 'projects']);
    anVisit(['tenant_id' => $ativa->id, 'user_id' => $u2->id, 'module' => 'finance', 'path' => 'transactions', 'created_at' => now()->subDays(2)]);
    anVisit(['tenant_id' => $ativa->id, 'user_id' => $u1->id, 'module' => 'projects', 'path' => 'projects', 'created_at' => now()->subDays(40)]);
    anVisit(['tenant_id' => $parada->id, 'user_id' => $u3->id, 'created_at' => now()->subDays(20)]);
    anVisit(['tenant_id' => null, 'user_id' => null, 'area' => 'admin', 'path' => 'admin']); // não conta
    anVisit(['area' => 'public', 'module' => 'other', 'path' => 'blog', 'visitor' => 'aaaa', 'source' => 'google', 'device' => 'mobile']);
    anVisit(['area' => 'public', 'module' => 'other', 'path' => 'blog', 'visitor' => 'bbbb', 'source' => 'direct', 'device' => 'desktop']);

    $a = app(ProductAnalyticsService::class)->build(30);

    expect($a['app']['orgs'])->toBe(2)
        ->and($a['app']['users'])->toBe(3)
        ->and($a['app']['dau'])->toBe(1)
        ->and($a['app']['wau'])->toBe(2)
        ->and($a['app']['paying'])->toBe(2)
        ->and($a['app']['trialing'])->toBe(1);

    expect(collect($a['health']['at_risk'])->pluck('name')->all())->toBe(['ONG Parada'])
        ->and(collect($a['health']['not_activated'])->pluck('name')->all())->toBe(['ONG Nova'])
        ->and($a['health']['top'][0]['name'])->toBe('ONG Ativa')
        ->and($a['health']['top'][0]['users'])->toBe(2);

    $modules = collect($a['modules'])->keyBy('key');
    expect($modules['projects']['orgs'])->toBe(1)->and($modules['projects']['adoption'])->toBe(50.0);

    expect($a['public']['visitors'])->toBe(2)
        ->and($a['public']['signups'])->toBe(1)
        ->and($a['public']['conversion'])->toBe(50.0)
        ->and(collect($a['public']['sources'])->pluck('key')->sort()->values()->all())->toBe(['direct', 'google']);
});

it('página de analytics abre para o super admin com qualquer período', function () {
    $admin = anAdmin();
    anVisit(['tenant_id' => $admin->tenant_id, 'user_id' => $admin->id, 'module' => 'projects']);

    foreach ([7, 30, 90, 999] as $d) {
        $this->actingAs($admin)->get(route('admin.analytics', ['dias' => $d]))
            ->assertOk()->assertSee('Uso do produto pelos clientes')->assertSee('Saúde das organizações')->assertSee('Site público');
    }

    $user = User::factory()->create(['role' => 'manager']);
    $this->actingAs($user)->get(route('admin.analytics'))->assertForbidden();
});
