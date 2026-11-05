<?php

use App\Models\PageVisit;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SiteAnalyticsService;
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
        ->and(VisitClassifier::area('lp/teste-Os9vFT', null))->toBe('clients')
        ->and(VisitClassifier::area('transparencia/ong-luz', 7))->toBe('clients')
        ->and(VisitClassifier::area('lpx', null))->toBe('public')
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
        ['tenant_id' => null, 'user_id' => null, 'path' => 'lp/teste-Os9vFT', 'created_at' => now()],
    ]);

    $migration->up();

    $rows = DB::table('page_visits')->orderBy('id')->get();
    expect($rows)->toHaveCount(4) // polling apagado
        ->and($rows[0]->path)->toBe('projects/{id}/edit')->and($rows[0]->area)->toBe('app')->and($rows[0]->module)->toBe('projects')
        ->and($rows[0]->visitor)->toBe('u' . $client->id)
        ->and($rows[1]->area)->toBe('admin')
        ->and($rows[2]->area)->toBe('public')->and($rows[2]->module)->toBe('other')->and($rows[2]->visitor)->toBeNull()
        ->and($rows[3]->area)->toBe('clients');
});

it('calcula visitas, visitantes, origem, dispositivo, páginas e áreas como no blog', function () {
    $this->travelTo(now()->setTime(15, 0));
    $org  = Tenant::factory()->create(['name' => 'Instituto Luz']);
    $user = User::factory()->create(['tenant_id' => $org->id, 'role' => 'ngo']);

    // período atual (7 dias)
    anVisit(['area' => 'public', 'module' => 'other', 'path' => '/', 'visitor' => 'v1', 'source' => 'google', 'device' => 'mobile']);
    anVisit(['area' => 'public', 'module' => 'other', 'path' => 'blog', 'visitor' => 'v1', 'source' => 'internal', 'device' => 'mobile']);
    anVisit(['area' => 'public', 'module' => 'other', 'path' => 'precos', 'visitor' => 'v2', 'source' => 'other', 'referrer_host' => 'portalong.org', 'device' => 'desktop', 'created_at' => now()->subDays(2)]);
    anVisit(['tenant_id' => $org->id, 'user_id' => $user->id, 'visitor' => 'u' . $user->id, 'path' => 'projects', 'module' => 'projects', 'device' => 'desktop']);
    anVisit(['area' => 'clients', 'module' => 'other', 'path' => 'lp/teste-Os9vFT', 'visitor' => 'v3', 'source' => 'instagram', 'device' => 'mobile']);
    anVisit(['area' => 'admin', 'path' => 'admin', 'user_id' => $user->id]); // não conta
    // período anterior
    anVisit(['area' => 'public', 'module' => 'other', 'path' => '/', 'visitor' => 'v9', 'created_at' => now()->subDays(10)]);

    $s = app(SiteAnalyticsService::class);
    $m = $s->summary(7);

    expect($m['views'])->toBe(5)
        ->and($m['prev_views'])->toBe(1)
        ->and($m['views_change'])->toBe(400.0)
        ->and($m['visitors'])->toBe(4)              // v1, v2, v3 e o usuário logado
        ->and($m['logged'])->toBe(1)
        ->and($m['per_visitor'])->toBe(1.3)
        ->and(array_sum($m['daily']['views']))->toBe(5)
        ->and($m['hours'][15])->toBe(5)
        ->and($m['referrers'])->toBe(['portalong.org' => 1])
        ->and($m['orgs'][0]['name'])->toBe('Instituto Luz');

    expect(collect($m['areas'])->pluck('count', 'key')->all())->toBe(['public' => 3, 'app' => 1, 'clients' => 1]);
    expect(collect($m['sources'])->pluck('count', 'key')->all())->toEqualCanonicalizing(['google' => 1, 'internal' => 1, 'other' => 1, 'instagram' => 1]);
    expect(collect($m['devices'])->pluck('count', 'key')->all())->toEqualCanonicalizing(['mobile' => 3, 'desktop' => 2]);
    expect($m['pages'][0]['path'])->toBeIn(['/', '/blog', '/precos', '/projects', '/lp/teste-Os9vFT']);

    // filtro por área
    expect($s->summary(7, 'public')['views'])->toBe(3)
        ->and($s->summary(7, 'app')['views'])->toBe(1)
        ->and($s->summary(7, 'clients')['views'])->toBe(1)
        ->and($s->summary(7, 'xyz')['area'])->toBe('tudo')
        ->and($s->summary(13)['days'])->toBe(30);
});

it('página de analytics abre para o super admin com qualquer período', function () {
    $admin = anAdmin();
    anVisit(['tenant_id' => $admin->tenant_id, 'user_id' => $admin->id, 'module' => 'projects']);

    foreach ([7, 30, 90, 365, 999] as $d) {
        foreach (['tudo', 'public', 'app', 'clients'] as $area) {
            $this->actingAs($admin)->get(route('admin.analytics', ['dias' => $d, 'area' => $area]))
                ->assertOk()->assertSee('Acessos ao sistema')->assertSee('Visitantes únicos')->assertSee('Páginas mais acessadas');
        }
    }

    $user = User::factory()->create(['role' => 'manager']);
    $this->actingAs($user)->get(route('admin.analytics'))->assertForbidden();
});
