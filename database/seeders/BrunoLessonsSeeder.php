<?php

namespace Database\Seeders;

use App\Models\BrunoLesson;
use Illuminate\Database\Seeder;

/**
 * Seed inicial de licoes do Bruno cobrindo os padroes de venda ONG mais
 * recorrentes: objecao de verba, concorrente, desconfianca IA, edital
 * urgente, equipe grande. Idempotente (updateOrCreate por title).
 *
 * Rodar: php artisan db:seed --class=BrunoLessonsSeeder
 */
class BrunoLessonsSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            [
                'title'         => 'ONG pequena sem verba — fecha com origem + Cristiane',
                'tags'          => ['ong-pequena', 'objecao-verba', 'ong-em-captacao'],
                'situation'     => 'Lead ONG pequena diz que nao tem verba pra pagar sistema. Objecao emocional mais comum.',
                'lead_said'     => 'Somos uma ONG pequena, com voluntariado, R$ 429/mes fica pesado pra gente',
                'bruno_replied' => 'Entendo — e eh justamente ONG pequena que mais perde tempo em planilha porque nao tem equipe pra isso. A Vivensi nasceu de gente que tocava projeto social em Araraquara e vivia essa mesma dor. Pra ONGs em captacao inicial avaliamos condicao especial caso a caso — me manda em 2 linhas nome da ONG, cidade e o que voces fazem. Passo pra Cristiane analisar hoje.',
                'notes'         => 'Padrao mais comum. Combina origem (nivelamento) + condicao especial (nao trial, mas proximo). Nunca oferece demo antes de ouvir.',
            ],
            [
                'title'         => 'Ja uso concorrente — nao bate, redireciona pra dor especifica de ONG',
                'tags'          => ['concorrente-atual', 'desconfianca-vendor', 'objecao-mudanca'],
                'situation'     => 'Lead menciona que usa outro sistema (Bitrix, RD, Salesforce, planilha estruturada) e ta satisfeito.',
                'lead_said'     => 'Ja uso Bitrix / RD / uma planilha que meu contador me passou, ta funcionando',
                'bruno_replied' => 'Legal, esse sistema eh solido pra varias coisas. Sem bater no concorrente — o que te frustra hoje na parte de ONG especificamente? Tipo cadastro de beneficiario, prestacao MROSC, chamada por token publico. Geralmente eh ai que sistema generico trava. Se nao trava na sua realidade, sou o primeiro a te falar que nao vale trocar.',
                'notes'         => 'NUNCA bater no concorrente diretamente. Foca em identificar a dor especifica de ONG que sistema generico nao resolve.',
            ],
            [
                'title'         => 'Nao confia em IA — Bruce so sugere, humano aprova',
                'tags'          => ['desconfianca-vendor', 'bruce-ia'],
                'situation'     => 'Lead com receio de IA substituir humano ou responder besteira pro doador/beneficiario.',
                'lead_said'     => 'Nao confio em IA, ela erra muito e vai responder besteira pro meu doador',
                'bruno_replied' => 'Faz sentido esse receio, muita gente vem com ele. A Bruce so sugere — todo texto, analise ou resposta passa por voce aprovar antes de sair. E ela eh treinada especificamente na base da sua ONG, nao eh ChatGPT solto. Se ver rodando 20 min voce julga se faz sentido no teu contexto — sem compromisso.',
                'notes'         => 'Aqui demo faz sentido — lead tem sinal de compra (esta questionando profundamente). Diferente da desconfianca vaga.',
            ],
            [
                'title'         => 'Perdeu edital anterior — Radar como argumento (urgencia positiva)',
                'tags'          => ['radar-editais', 'ong-em-captacao', 'edital-proximo'],
                'situation'     => 'Lead reclama que perdeu prazo de edital ou tem projeto critico dependendo de chamamento.',
                'lead_said'     => 'Perdi o ultimo chamamento MROSC porque nao vi a tempo, agora ta saindo outro',
                'bruno_replied' => 'Isso eh comum — a maioria das ONGs monitora edital no boletim oficial ou WhatsApp de amigo, e sempre escapa um. O Radar de Editais do Vivensi mostra chamamentos MROSC/SUAS abertos com prazo, valor e enquadramento por area. Antes de agendar demo, quer que eu te mostre ja 3 editais abertos agora pra tua area de atuacao? Assim voce ve valendo hoje mesmo.',
                'notes'         => 'Value-first: entrega valor concreto (3 editais abertos) ANTES de pedir demo. Converte muito melhor.',
            ],
            [
                'title'         => 'Equipe grande com pouca familiaridade tech — desmistifica onboarding',
                'tags'          => ['ong-consolidada', 'objecao-mudanca', 'objecao-tempo'],
                'situation'     => 'ONG com 10+ pessoas (varias voluntarias/pouca familiaridade digital) preocupada com treinamento.',
                'lead_said'     => 'Somos 15 pessoas, tem gente que mal usa email direito, vai dar trabalho treinar',
                'bruno_replied' => 'Essa preocupacao eh legitima. O Vivensi foi desenhado pra ser usado por voluntario — os modulos abrem em uma tela so, sem menu profundo. Fluxos comuns (registrar atendimento, marcar presenca, subir doc) levam 2-3 cliques. Fazemos onboarding em 1h com o time todo junto, gravado, pra quem faltar rever depois. Curva de aprendizado real eh 3-5 dias, nao semanas.',
                'notes'         => 'Numero concreto (1h onboarding, 3-5 dias curva) desarma objecao vaga de "vai dar trabalho".',
            ],
        ];

        foreach ($lessons as $data) {
            // updateOrCreate por title garante idempotencia
            BrunoLesson::updateOrCreate(
                ['title' => $data['title'], 'tenant_id' => null],
                array_merge($data, ['active' => true])
            );
        }
    }
}
