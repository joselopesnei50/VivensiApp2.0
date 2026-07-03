<?php

namespace Tests\Feature\Ngo;

use App\Jobs\GeocodeAddressJob;
use App\Models\Beneficiary;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Importação de beneficiários por planilha CSV — modelo novo de 16 colunas
 * (dados sociais + endereço estruturado), compatibilidade com o modelo legado
 * de 7 colunas, normalização de enums/datas BR e deduplicação por CPF/NIS.
 */
class BeneficiaryImportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([GeocodeAddressJob::class]);

        $this->tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'ngo',
            'email'     => 'ngo_' . uniqid() . '@example.com',
        ]);
        $this->actingAs($this->user);
    }

    private function importCsv(string $content)
    {
        $file = UploadedFile::fake()->createWithContent('planilha.csv', $content);

        return $this->post('/ngo/beneficiaries/import', ['file' => $file]);
    }

    /** @test */
    public function modelo_novo_importa_dados_sociais_e_endereco_estruturado(): void
    {
        $csv = "Nome,NIS,CPF,Data_Nascimento,Genero,Raca_Cor,Escolaridade,Telefone,CEP,Rua,Numero,Complemento,Bairro,Cidade,UF,Status\n"
             . "Maria da Silva,12345678910,123.456.789-10,20/05/1985,Feminino,Parda,Médio completo,(11) 99999-9999,01001-000,Rua Exemplo,123,Casa B,Centro,São Paulo,sp,ativo\n";

        $this->importCsv($csv)->assertSessionHas('success');

        $b = Beneficiary::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame('Maria da Silva', $b->name);
        $this->assertSame('12345678910', $b->cpf); // desencriptado pelo accessor
        $this->assertSame('1985-05-20', $b->birth_date->toDateString());
        $this->assertSame('feminino', $b->gender);
        $this->assertSame('parda', $b->race_color);
        $this->assertSame('medio_completo', $b->education);
        $this->assertSame('01001-000', $b->address_zip);
        $this->assertSame('SP', $b->address_state);
        $this->assertSame('active', $b->status);
        $this->assertStringContainsString('Rua Exemplo, 123', $b->address);
        $this->assertStringContainsString('São Paulo', $b->address);

        Bus::assertDispatched(GeocodeAddressJob::class, 1);
    }

    /** @test */
    public function planilha_legada_de_sete_colunas_continua_aceita(): void
    {
        $csv = "Nome,NIS,CPF,Data_Nascimento,Telefone,Endereco,Status\n"
             . "João Legado,98765432100,,1990-03-20,(11) 98888-7777,\"Rua Antiga, 45 - Centro\",inactive\n";

        $this->importCsv($csv)->assertSessionHas('success');

        $b = Beneficiary::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame('João Legado', $b->name);
        $this->assertSame('98765432100', $b->nis);
        $this->assertSame('1990-03-20', $b->birth_date->toDateString());
        $this->assertSame('Rua Antiga, 45 - Centro', $b->address);
        $this->assertSame('inactive', $b->status);
        $this->assertNull($b->gender);
    }

    /** @test */
    public function duplicata_por_cpf_atualiza_em_vez_de_criar(): void
    {
        Beneficiary::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Maria Antiga',
            'cpf'       => '12345678910',
            'status'    => 'active',
        ]);

        $csv = "Nome,CPF,Telefone,Status\n"
             . "Maria Atualizada,123.456.789-10,(11) 91111-2222,graduado\n";

        $this->importCsv($csv)->assertSessionHas('success');

        $this->assertSame(1, Beneficiary::where('tenant_id', $this->tenant->id)->count());
        $b = Beneficiary::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame('Maria Atualizada', $b->name);
        $this->assertSame('graduated', $b->status);
    }

    /** @test */
    public function enum_invalido_vira_nulo_com_aviso_sem_perder_a_linha(): void
    {
        $csv = "Nome,Genero,Data_Nascimento\n"
             . "Pedro Souza,marciano,31/02/2020\n";

        $response = $this->importCsv($csv);

        $response->assertSessionHas('warning');
        $response->assertSessionHas('import_errors');

        $b = Beneficiary::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame('Pedro Souza', $b->name);
        $this->assertNull($b->gender);
        $this->assertNull($b->birth_date);
    }

    /** @test */
    public function linha_sem_nome_conta_como_falha(): void
    {
        $csv = "Nome,CPF\n"
             . ",111.222.333-44\n"
             . "Ana Valida,555.666.777-88\n";

        $this->importCsv($csv)->assertSessionHas('warning');

        $this->assertSame(1, Beneficiary::where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function template_traz_colunas_sociais_e_endereco_estruturado(): void
    {
        $response = $this->get('/ngo/beneficiaries/import/template');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Genero', $content);
        $this->assertStringContainsString('Raca_Cor', $content);
        $this->assertStringContainsString('Escolaridade', $content);
        $this->assertStringContainsString('CEP', $content);
        $this->assertStringContainsString('Bairro', $content);
    }
}
