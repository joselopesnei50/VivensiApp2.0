<?php

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AudienceProfileService;
use Illuminate\Support\Carbon;

/*
| Perfil do público no relatório anual (gênero, faixa etária, cor/raça).
*/

beforeEach(function () {
    Carbon::setTestNow('2026-11-09 10:00:00');

    $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role'      => 'ngo',
        'email'     => 'ngo_' . uniqid() . '@example.com',
    ]);

    $this->benef = function (array $attrs, array $dates = ['2026-03-10'], ?int $tenantId = null, string $type = 'psicossocial') {
        $tenantId ??= $this->tenant->id;
        $b = Beneficiary::create(array_merge(['tenant_id' => $tenantId, 'name' => 'Pessoa ' . uniqid(), 'status' => 'active'], $attrs));
        foreach ($dates as $d) {
            Attendance::create([
                'tenant_id' => $tenantId, 'beneficiary_id' => $b->id, 'user_id' => $this->user->id,
                'date' => $d, 'type' => $type, 'description' => 'x',
            ]);
        }
        return $b;
    };

    $this->row = fn (array $rows, string $label) => collect($rows)->firstWhere('label', $label);
});

afterEach(fn () => Carbon::setTestNow());

it('conta cada pessoa atendida uma vez, por gênero, cor/raça e faixa etária', function () {
    ($this->benef)(['gender' => 'feminino', 'race_color' => 'preta', 'birth_date' => '2022-05-01'], ['2026-02-01', '2026-06-01', '2026-09-01']);
    ($this->benef)(['gender' => 'feminino', 'race_color' => 'parda', 'birth_date' => '2012-01-01']);
    ($this->benef)(['gender' => 'masculino', 'race_color' => 'branca', 'birth_date' => '1960-01-01']);
    ($this->benef)([]); // sem nada informado

    $p = app(AudienceProfileService::class)->build($this->tenant->id, '2026-01-01', '2026-12-31');

    expect($p['total'])->toBe(4)
        ->and(($this->row)($p['gender'], 'Feminino')['count'])->toBe(2)
        ->and(($this->row)($p['gender'], 'Feminino')['pct'])->toBe(50.0)
        ->and(($this->row)($p['gender'], 'Masculino')['count'])->toBe(1)
        ->and(($this->row)($p['gender'], 'Não informado')['count'])->toBe(1)
        ->and(($this->row)($p['race'], 'Preta')['count'])->toBe(1)
        ->and(($this->row)($p['race'], 'Parda')['count'])->toBe(1)
        ->and(($this->row)($p['age'], '0 a 5 anos (primeira infância)')['count'])->toBe(1)
        ->and(($this->row)($p['age'], '12 a 17 anos (adolescente)')['count'])->toBe(1)
        ->and(($this->row)($p['age'], '60 anos ou mais (pessoa idosa)')['count'])->toBe(1)
        ->and(($this->row)($p['age'], 'Não informado')['count'])->toBe(1);
});

it('calcula a idade no fim do período, não hoje', function () {
    // Faz 18 em 2026-03-01: no fim de 2025 tinha 17.
    ($this->benef)(['birth_date' => '2008-03-01'], ['2025-05-10']);

    $p = app(AudienceProfileService::class)->build($this->tenant->id, '2025-01-01', '2025-12-31');

    expect($p['referenceDate'])->toBe('31/12/2025')
        ->and(($this->row)($p['age'], '12 a 17 anos (adolescente)')['count'])->toBe(1);
});

it('respeita período, filtros de status e tipo, e não mistura organizações', function () {
    ($this->benef)(['gender' => 'feminino'], ['2025-12-31']);                       // fora do período
    ($this->benef)(['gender' => 'feminino', 'status' => 'inactive']);              // status diferente
    ($this->benef)(['gender' => 'masculino'], ['2026-04-04'], null, 'oficina');    // tipo diferente
    ($this->benef)(['gender' => 'feminino']);                                      // conta

    $other = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    ($this->benef)(['gender' => 'feminino'], ['2026-03-10'], $other->id);          // outra ONG

    $p = app(AudienceProfileService::class)->build($this->tenant->id, '2026-01-01', '2026-12-31', 'active', 'psicossocial');

    expect($p['total'])->toBe(1)
        ->and(($this->row)($p['gender'], 'Feminino')['count'])->toBe(1);
});

it('mostra a seção na tela do relatório anual', function () {
    ($this->benef)(['gender' => 'feminino', 'race_color' => 'indigena', 'birth_date' => '1990-01-01']);

    $this->actingAs($this->user)
        ->get('/ngo/beneficiaries/reports/annual?year=2026')
        ->assertOk()
        ->assertSee('Perfil do público atendido')
        ->assertSee('Indígena')
        ->assertSee('30 a 59 anos (adulto)')
        ->assertSee('CSV perfil do público');
});

it('exporta o perfil em CSV', function () {
    ($this->benef)(['gender' => 'feminino', 'race_color' => 'preta', 'birth_date' => '2015-06-01']);

    $r = $this->actingAs($this->user)->get('/ngo/beneficiaries/reports/annual/export.csv?format=profile&year=2026');
    $r->assertOk();
    $csv = $r->streamedContent();

    expect($r->headers->get('content-disposition'))->toContain('relatorio-social-perfil-publico-2026')
        ->and($csv)->toContain('Dimensão,Categoria,Pessoas,%')
        ->and($csv)->toContain('Gênero,Feminino,1,"100,0"')
        ->and($csv)->toContain('"Faixa etária","6 a 11 anos (criança)",1,"100,0"')
        ->and($csv)->toContain('Cor/raça,Preta,1,"100,0"');
});

it('o PDF do relatório anual inclui o perfil', function () {
    ($this->benef)(['gender' => 'masculino', 'birth_date' => '1950-01-01']);
    $profile = app(AudienceProfileService::class)->build($this->tenant->id, '2026-01-01', '2026-12-31');

    $html = view('ngo.beneficiaries.annual_report_pdf', [
        'year' => 2026, 'from' => '2026-01-01', 'to' => '2026-12-31', 'benefStatus' => '', 'type' => '',
        'totalAttendances' => 1, 'uniqueFamilies' => 1, 'monthly' => array_fill(1, 12, 0),
        'byType' => collect(), 'byUser' => collect(), 'topFamilies' => collect(),
        'orgName' => 'ONG', 'generatedAt' => '09/11/2026', 'emitter' => 'Fulana', 'profile' => $profile,
    ])->render();

    expect($html)->toContain('Perfil do público atendido')
        ->and($html)->toContain('60 anos ou mais (pessoa idosa)');
});
