<?php

namespace Tests\Feature\StrategyRoom;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectPerson;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\StrategyRoom\ProgramsAgentTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sala de Estrategia — Fase 4. Tools do Agente de Programas (Sofia).
 *
 * Valida agregacao (nunca PII) e o espelhamento do criterio de risco do
 * modulo Lista de Presenca: 3 faltas puras seguidas OU >=30% de ausencia.
 */
class ProgramsAgentToolsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant  = Tenant::factory()->create();
        $this->project = Project::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'active',
        ]);
    }

    private function makeSession(string $date): ClassSession
    {
        return ClassSession::create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'title'      => "Aula {$date}",
            'date'       => $date,
        ]);
    }

    private function makePerson(): ProjectPerson
    {
        return ProjectPerson::create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'name'       => 'Beneficiario Teste',
        ]);
    }

    private function attend(ClassSession $session, ProjectPerson $person, string $status): void
    {
        ClassAttendance::create([
            'tenant_id'         => $this->tenant->id,
            'class_session_id'  => $session->id,
            'project_person_id' => $person->id,
            'status'            => $status,
            'checked_in_via'    => 'professor',
        ]);
    }

    /** @test */
    public function frequencia_sem_aulas_devolve_lacuna(): void
    {
        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $this->tenant->id);

        $this->assertTrue($out['success']);
        $this->assertSame(0, $out['total_aulas']);
        $this->assertSame([], $out['projetos']);
    }

    /** @test */
    public function tres_faltas_seguidas_marcam_risco_de_evasao(): void
    {
        $person = $this->makePerson();

        // 5 aulas: presente nas 2 primeiras, falta nas 3 mais recentes.
        foreach ([['-20 days', 'presente'], ['-15 days', 'presente'], ['-10 days', 'falta'], ['-5 days', 'falta'], ['-1 days', 'falta']] as [$when, $status]) {
            $this->attend($this->makeSession(now()->modify($when)->toDateString()), $person, $status);
        }

        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $this->tenant->id);

        $this->assertSame(1, $out['total_em_risco']);
        $this->assertSame(1, $out['projetos'][0]['em_risco_evasao']);
        $this->assertSame(1, $out['projetos'][0]['beneficiarios']);
        $this->assertSame(5, $out['projetos'][0]['aulas']);
    }

    /** @test */
    public function justificada_quebra_sequencia_e_fica_fora_do_percentual(): void
    {
        $person = $this->makePerson();

        // 2 faltas recentes + justificada no meio: sequencia = 2 (< 3).
        // Percentual: 7 presencas / (7 presencas + 2 faltas) = 22% ausencia (< 30%).
        foreach ([
            ['-30 days', 'presente'], ['-27 days', 'presente'], ['-24 days', 'presente'],
            ['-21 days', 'presente'], ['-18 days', 'presente'], ['-15 days', 'presente'],
            ['-12 days', 'presente'],
            ['-9 days', 'falta_justificada'],
            ['-5 days', 'falta'], ['-1 days', 'falta'],
        ] as [$when, $status]) {
            $this->attend($this->makeSession(now()->modify($when)->toDateString()), $person, $status);
        }

        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $this->tenant->id);

        $this->assertSame(0, $out['total_em_risco']);
        $this->assertSame(1, $out['projetos'][0]['faltas_justificadas']);
    }

    /** @test */
    public function trinta_por_cento_de_ausencia_marca_risco(): void
    {
        $person = $this->makePerson();

        // 6 presencas + 4 faltas intercaladas (nunca 3 seguidas) = 40% ausencia.
        foreach ([
            ['-30 days', 'falta'], ['-27 days', 'presente'], ['-24 days', 'falta'],
            ['-21 days', 'presente'], ['-18 days', 'falta'], ['-15 days', 'presente'],
            ['-12 days', 'presente'], ['-9 days', 'falta'], ['-5 days', 'presente'],
            ['-1 days', 'presente'],
        ] as [$when, $status]) {
            $this->attend($this->makeSession(now()->modify($when)->toDateString()), $person, $status);
        }

        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $this->tenant->id);

        $this->assertSame(1, $out['total_em_risco']);
    }

    /** @test */
    public function payload_de_frequencia_nao_contem_nome_de_beneficiario(): void
    {
        $person = $this->makePerson(); // name = 'Beneficiario Teste'
        $this->attend($this->makeSession(now()->subDay()->toDateString()), $person, 'falta');

        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $this->tenant->id);

        $this->assertStringNotContainsString(
            'Beneficiario Teste',
            json_encode($out, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @test */
    public function nao_vaza_frequencia_de_outro_tenant(): void
    {
        $person = $this->makePerson();
        $this->attend($this->makeSession(now()->subDay()->toDateString()), $person, 'presente');

        $outroTenant = Tenant::factory()->create();
        $out = ProgramsAgentTools::execute('frequencia_e_evasao', [], $outroTenant->id);

        $this->assertSame(0, $out['total_aulas']);
    }

    /** @test */
    public function execucao_agrega_tarefas_e_vencidas_por_projeto(): void
    {
        Task::create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'title'      => 'Feita',
            'status'     => 'completed',
        ]);
        Task::create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'title'      => 'Vencida',
            'status'     => 'pending',
            'due_date'   => now()->subDays(3)->toDateString(),
        ]);
        Task::create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'title'      => 'No prazo',
            'status'     => 'pending',
            'due_date'   => now()->addDays(3)->toDateString(),
        ]);

        $out = ProgramsAgentTools::execute('execucao_de_tarefas', [], $this->tenant->id);

        $this->assertTrue($out['success']);
        $this->assertSame(3, $out['total_tarefas']);
        $this->assertSame(1, $out['total_vencidas']);

        $projeto = $out['projetos'][0];
        $this->assertSame(1, $projeto['concluidas']);
        $this->assertSame(1, $projeto['vencidas']);
        $this->assertEqualsWithDelta(33.3, $projeto['pct_conclusao'], 0.1);
    }

    /** @test */
    public function execucao_sem_projeto_ativo_devolve_vazio(): void
    {
        $this->project->update(['status' => 'completed']);

        $out = ProgramsAgentTools::execute('execucao_de_tarefas', [], $this->tenant->id);

        $this->assertSame([], $out['projetos']);
    }

    /** @test */
    public function tool_desconhecida_devolve_erro(): void
    {
        $out = ProgramsAgentTools::execute('nao_existe', [], $this->tenant->id);
        $this->assertArrayHasKey('error', $out);
    }
}
