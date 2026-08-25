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
            [
                'title'         => 'ONG que atende criancas/adolescentes — LGPD reforcada + ECA',
                'tags'          => ['lgpd', 'ong-consolidada', 'desconfianca-vendor'],
                'situation'     => 'ONG que atende menores (SUAS/CRAS, abrigo, contraturno) preocupada com privacidade de dados sensiveis de criancas.',
                'lead_said'     => 'A gente cadastra criancas e adolescentes. Como voces garantem que os dados nao vazam? Estamos com medo por causa da LGPD.',
                'bruno_replied' => 'Essa preocupacao eh certeira e a maioria dos sistemas nao trata direito. O Vivensi eh LGPD-first: dados sensiveis (CPF, endereco, foto) sao criptografados no banco (AES-256), auditoria completa de quem acessou o que, opt-in explicito por responsavel legal com armazenamento do termo, e portal do titular pra familia exportar ou excluir os dados a qualquer momento. Pra ECA especificamente, temos campo separado pra guarda/responsavel e o consentimento eh vinculado ao responsavel maior de idade. Podemos falar de auditoria e DPO na demo, se voce quiser aprofundar.',
                'notes'         => 'Nicho critico. Menciona AES-256, auditoria, portal titular, DPO — features reais do Vivensi (memoria LGPD Self-Service C3 + C4 encryption). NAO invente.',
            ],
            [
                'title'         => 'Migracao de planilha grande — desmistifica perda de historico',
                'tags'          => ['objecao-mudanca', 'ong-consolidada'],
                'situation'     => 'ONG com anos de dados em Excel/Google Sheets com medo de perder historico na migracao.',
                'lead_said'     => 'Temos 5 anos de dados de beneficiarios numa planilha do Google, nao posso perder isso',
                'bruno_replied' => 'Ninguem migra pra perder dado — a gente importa a planilha inteira: nome, CPF, endereco, historico de atendimentos, tudo. Se sua planilha esta bem estruturada, sao 15 min de import. Se esta bagunçada (varias abas, colunas com nomes diferentes por ano), a Cristiane senta com voce numa call de 30 min pra mapear coluna por coluna antes. Voce sai com a base migrada e o Excel arquivado como backup, sem perder um registro.',
                'notes'         => 'Numero concreto (15 min import limpo, 30 min call se bagunçado). Menciona backup do Excel — desarma medo de perda.',
            ],
            [
                'title'         => 'Decisao passa por conselho/diretoria — Bruno cria material pro lead defender',
                'tags'          => ['objecao-tempo', 'ong-consolidada', 'proposta-enviada'],
                'situation'     => 'Lead gostou mas nao tem autonomia — precisa apresentar pra conselho, diretoria ou coordenacao antes de decidir.',
                'lead_said'     => 'Gostei bastante, mas essa decisao passa pelo nosso conselho. Reunimos so na proxima semana',
                'bruno_replied' => 'Perfeito, isso eh saudavel — decisao coletiva pra ONG eh melhor mesmo. Vou te mandar um resumo de 1 pagina com: (1) o problema que resolvemos, (2) modulos incluidos no plano, (3) custo mensal e o que substitui, (4) 3 depoimentos curtos de ONGs parecidas. Pra facilitar o conselho analisar. Me passa seu email que envio ainda hoje. Depois da reuniao, se aprovar, agendamos a implementacao — se tiver duvida especifica que surgiu la, ja resolvemos junto.',
                'notes'         => 'Fase 4 do funil (proposta). NAO oferecer demo — lead ja passou dessa fase. Foco: dar munição pra ele DEFENDER internamente. Captura email = avanca.',
            ],
            [
                'title'         => 'Prestacao de contas urgente — cria urgencia real, nao artificial',
                'tags'          => ['prestacao-contas', 'ong-em-captacao', 'objecao-tempo'],
                'situation'     => 'ONG com auditoria/prestacao de contas em cima do prazo (municipal, estadual, federal, financiador privado).',
                'lead_said'     => 'To com auditoria do municipio semana que vem e minha prestacao ta na planilha, vai dar ruim',
                'bruno_replied' => 'Deu pra sentir a pressao aqui. A boa noticia eh que da pra virar isso rapido: importamos os dados que voce tem, geramos o relatorio de prestacao pronto pra CEBAS/MROSC/municipio em PDF assinado digital com trilha de auditoria, e voce entrega no prazo. Se conseguirmos comecar hoje, ate quarta ta pronto. Vou passar teu contato pra Cristiane priorizar — te chama nas proximas 2h. Me confirma so o municipio e o tipo de prestacao (municipal? convenio?), pra ela ja chegar com o padrao certo.',
                'notes'         => 'Value-first + escalada rapida. NUNCA prometer prazo sem conferir com Cristiane — escalar salva. Perguntar municipio/tipo mostra dominio.',
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
