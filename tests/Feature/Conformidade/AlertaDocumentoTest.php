<?php

namespace Tests\Feature\Conformidade;

use App\Jobs\AlertaDocumentoVencendoJob;
use App\Mail\DocumentoVencendoMail;
use App\Models\Attachment;
use App\Models\RegraAvaliacao;
use App\Models\RequisitoLegal;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RegraAvaliacaoSeeder;
use Database\Seeders\RequisitoLegalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlertaDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequisitoLegalSeeder::class);
        $this->seed(RegraAvaliacaoSeeder::class);

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->admin  = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
    }

    // ── AlertaDocumentoVencendoJob ────────────────────────────────────────────

    public function test_job_envia_email_para_documento_vencendo(): void
    {
        Mail::fake();

        $regra = RegraAvaliacao::whereNotNull('tipo_documento_obrigatorio')
            ->whereNotNull('alerta_dias_antes')
            ->first();

        $this->assertNotNull($regra, 'Precisa de uma RegraAvaliacao com tipo_documento_obrigatorio');

        Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'certificado.pdf',
            'path'            => 'private/tenants/1/conformidade/fake.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 1024,
            'uploaded_by'     => $this->admin->id,
            'tipo_documento'  => $regra->tipo_documento_obrigatorio,
            'valid_until'     => now()->addDays($regra->alerta_dias_antes - 5),
            'alerta_enviado_em' => null,
        ]);

        (new AlertaDocumentoVencendoJob())->handle();

        Mail::assertSent(DocumentoVencendoMail::class);
    }

    public function test_job_nao_reenvia_alerta_ja_enviado(): void
    {
        Mail::fake();

        $regra = RegraAvaliacao::whereNotNull('tipo_documento_obrigatorio')
            ->whereNotNull('alerta_dias_antes')
            ->first();

        if (! $regra) {
            $this->markTestSkipped('Nenhuma regra com tipo_documento_obrigatorio.');
        }

        Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'cert.pdf',
            'path'            => 'private/tenants/1/conformidade/fake2.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 1024,
            'uploaded_by'     => $this->admin->id,
            'tipo_documento'  => $regra->tipo_documento_obrigatorio,
            'valid_until'     => now()->addDays(5),
            'alerta_enviado_em' => now()->subDay(), // já enviado
        ]);

        (new AlertaDocumentoVencendoJob())->handle();

        Mail::assertNothingSent();
    }

    public function test_job_nao_alerta_documento_ainda_dentro_do_prazo(): void
    {
        Mail::fake();

        $regra = RegraAvaliacao::whereNotNull('tipo_documento_obrigatorio')
            ->whereNotNull('alerta_dias_antes')
            ->first();

        if (! $regra) {
            $this->markTestSkipped('Nenhuma regra com tipo_documento_obrigatorio.');
        }

        Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'cert.pdf',
            'path'            => 'private/tenants/1/conformidade/fake3.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 1024,
            'uploaded_by'     => $this->admin->id,
            'tipo_documento'  => $regra->tipo_documento_obrigatorio,
            'valid_until'     => now()->addDays($regra->alerta_dias_antes + 30), // fora do prazo de alerta
            'alerta_enviado_em' => null,
        ]);

        (new AlertaDocumentoVencendoJob())->handle();

        Mail::assertNothingSent();
    }

    // ── Upload de documento Tipo B ────────────────────────────────────────────

    public function test_upload_documento_tipo_b_cria_attachment(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->actingAs($this->admin);

        $req = RequisitoLegal::where('tipo', 'B')->first();
        $this->assertNotNull($req, 'Precisa de um RequisitoLegal Tipo B');

        $response = $this->post(route('ngo.conformidade.upload', $req->id), [
            'arquivo'     => UploadedFile::fake()->create('estatuto.pdf', 512, 'application/pdf'),
            'valid_until' => now()->addYear()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attachments', [
            'tenant_id'      => $this->tenant->id,
            'tipo_documento' => $req->regra->tipo_documento_obrigatorio,
        ]);

        Queue::assertPushed(\App\Jobs\RecalcularConformidadeJob::class);
    }

    public function test_upload_sem_arquivo_falha_validacao(): void
    {
        $this->actingAs($this->admin);

        $req = RequisitoLegal::where('tipo', 'B')->first();
        if (! $req) {
            $this->markTestSkipped('Nenhum RequisitoLegal Tipo B.');
        }

        $response = $this->post(route('ngo.conformidade.upload', $req->id), []);

        $response->assertSessionHasErrors('arquivo');
    }

    public function test_upload_form_renderiza_para_admin(): void
    {
        $this->actingAs($this->admin);

        $req = RequisitoLegal::where('tipo', 'B')->first();
        if (! $req) {
            $this->markTestSkipped('Nenhum RequisitoLegal Tipo B.');
        }

        $this->get(route('ngo.conformidade.upload.form', $req->id))
             ->assertStatus(200)
             ->assertSee('Enviar Documento');
    }

    public function test_download_documento_tenant_correto(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);

        $path = "private/tenants/{$this->tenant->id}/conformidade/test.pdf";
        Storage::disk('local')->put($path, 'fake pdf content');

        $doc = Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'test.pdf',
            'path'            => $path,
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 100,
            'uploaded_by'     => $this->admin->id,
        ]);

        $this->get(route('ngo.conformidade.download', $doc->id))
             ->assertStatus(200);
    }

    public function test_download_documento_outro_tenant_bloqueado(): void
    {
        Storage::fake('local');

        $outraTenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $outroAdmin  = User::factory()->create(['tenant_id' => $outraTenant->id, 'role' => 'ngo']);

        $this->actingAs($outroAdmin);

        $doc = Attachment::create([
            'tenant_id'       => $this->tenant->id,
            'attachable_type' => Tenant::class,
            'attachable_id'   => $this->tenant->id,
            'original_name'   => 'secret.pdf',
            'path'            => 'private/tenants/1/conformidade/secret.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => 100,
            'uploaded_by'     => $this->admin->id,
        ]);

        $this->get(route('ngo.conformidade.download', $doc->id))
             ->assertStatus(404);
    }
}
