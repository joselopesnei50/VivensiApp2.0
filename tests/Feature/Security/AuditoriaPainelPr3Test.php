<?php

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Beneficiary;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Auditoria do painel (26/11/2026), itens baixos e limpeza:
 *  7 CSV sem fórmula · 8 limite no SIC · 9 HTML da IA/CNPJ como texto ·
 *  10 anexos seguem o módulo · E3 rotas show sem método · E4 rota duplicada.
 */
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p3Admin(): array
{
    $t = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    return [$t, User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo', 'status' => 'active'])];
}

it('Csv::cell neutraliza fórmula e preserva números', function () {
    expect(Csv::cell('=HYPERLINK("http://x","clique")'))->toBe('\'=HYPERLINK("http://x","clique")');
    expect(Csv::cell('+5511'))->toBe("'+5511");
    expect(Csv::cell('@SUM(A1)'))->toBe("'@SUM(A1)");
    expect(Csv::cell('-150,00'))->toBe('-150,00');
    expect(Csv::cell('-1.234,56'))->toBe('-1.234,56');
    expect(Csv::cell('Maria'))->toBe('Maria');
    expect(Csv::cell(42))->toBe(42);
    expect(Csv::cell(null))->toBeNull();
});

it('exportação de leads da landing sai sem fórmula', function () {
    [$t, $u] = p3Admin();
    $lp = LandingPage::create(['tenant_id' => $t->id, 'title' => 'L', 'slug' => 'l-' . uniqid(), 'status' => 'published']);
    $this->post("/lp/{$lp->slug}/lead", ['name' => '=HYPERLINK("http://mal.example","Abrir")', 'email' => 'x@exemplo.com', 'consent_given' => 1]);
    expect(DB::table('landing_page_leads')->count())->toBe(1);

    $csv = $this->actingAs($u)->get("/ngo/landing-pages/{$lp->id}/leads/export")->streamedContent();
    expect($csv)->toContain("'=HYPERLINK")->not->toMatch('/(^|[;,"])=HYPERLINK/m');
});

it('consulta pública do protocolo do SIC tem limite de tentativas', function () {
    for ($i = 0; $i < 20; $i++) {
        $this->get("/transparencia/org-exemplo/sic/ABC{$i}XYZ");
    }
    $this->get('/transparencia/org-exemplo/sic/ZZZZZZ')->assertStatus(429);
});

it('colaborador não abre anexos de patrimônio, mas abre os de beneficiário', function () {
    Storage::fake('local');
    [$t] = p3Admin();
    $colab = User::factory()->create(['tenant_id' => $t->id, 'role' => 'employee', 'status' => 'active']);
    $asset = Asset::create(['tenant_id' => $t->id, 'name' => 'Notebook', 'code' => 'PAT-1', 'acquisition_date' => '2026-01-15', 'value' => 100, 'status' => 'active']);
    Storage::disk('local')->put('att/a.pdf', '%PDF');
    $att = Attachment::create(['tenant_id' => $t->id, 'attachable_type' => Asset::class, 'attachable_id' => $asset->id,
        'original_name' => 'nota.pdf', 'path' => 'att/a.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 4]);

    $this->actingAs($colab)->get("/attachments/asset/{$asset->id}")->assertForbidden();
    $this->actingAs($colab)->get("/attachments/{$att->id}/download")->assertForbidden();
    $this->actingAs($colab)->delete("/attachments/{$att->id}")->assertForbidden();

    $b = Beneficiary::create(['tenant_id' => $t->id, 'name' => 'Ana (exemplo)', 'status' => 'active']);
    $this->actingAs($colab)->get("/attachments/beneficiary/{$b->id}")->assertOk();
});

it('telas da IA e da consulta de CNPJ escapam o HTML', function () {
    $sa = file_get_contents(resource_path('views/ngo/smart_analysis/index.blade.php'));
    expect($sa)->toContain('marked.parse(saEsc(data.analysis))');
    $cf = file_get_contents(resource_path('views/ngo/conformidade/configurar.blade.php'));
    expect($cf)->toContain('cfEsc(data.razao_social')->toContain('cfEsc(data.error)');
});

it('rotas show do admin sem método não existem mais; rota duplicada da API removida', function () {
    $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => implode('|', $r->methods()) . ' ' . $r->uri());
    foreach (['admin/plans/{plan}', 'admin/blog/{blog}', 'admin/testimonials/{testimonial}', 'admin/academy/{academy}'] as $uri) {
        expect($uris)->not->toContain('GET|HEAD ' . $uri);
    }
    expect($uris)->not->toContain('GET|HEAD api/api/whatsapp/templates');
    expect($uris)->toContain('GET|HEAD api/whatsapp/templates');
});
