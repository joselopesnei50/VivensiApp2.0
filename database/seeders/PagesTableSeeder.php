<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PagesTableSeeder extends Seeder
{
    public function run()
    {
        // 1. Termos de Uso
        $termsContent = <<<'EOD'
<div class="legal-content">
    <h4>1. Aceitação dos Termos</h4>
    <p>Ao acessar e usar a plataforma Vivensi, você concorda em cumprir e ficar vinculado aos seguintes termos e condições de uso.</p>

    <h4>2. Descrição do Serviço</h4>
    <p>A Vivensi fornece uma plataforma de gestão para ONGs, Gerenciamento de Projetos e Finanças Pessoais. Reservamo-nos o direito de modificar, suspender ou descontinuar qualquer aspecto do serviço a qualquer momento.</p>

    <h4>3. Cadastro e Conta</h4>
    <p>Para usar certos recursos, você deve se registrar. Você concorda em fornecer informações precisas e manter a segurança de sua senha.</p>
    
    <h4>4. Planos e Pagamentos</h4>
    <p>O acesso a recursos premium requer uma assinatura. Os pagamentos são processados de forma segura e são recorrentes de acordo com o plano escolhido.</p>

    <h4>5. Cancelamento</h4>
    <p>Você pode cancelar sua assinatura a qualquer momento através do painel de controle. O cancelamento entrará em vigor no final do ciclo de faturamento atual.</p>

    <h4>6. Responsabilidades</h4>
    <p>A Vivensi não se responsabiliza por danos diretos, indiretos, incidentais ou consequenciais resultantes do uso ou da incapacidade de usar o serviço.</p>
    
    <h4>7. Contato</h4>
    <p>Para dúvidas sobre estes termos, entre em contato através de nosso suporte.</p>
</div>
EOD;

        Page::updateOrCreate(
            ['slug' => 'termos'],
            [
                'title' => 'Termos de Uso',
                'content' => $termsContent
            ]
        );

        // 2. Política de Privacidade
        $privacyContent = <<<'EOD'
<div class="legal-content">
    <h4>1. Coleta de Informações</h4>
    <p>Coletamos informações que você nos fornece diretamente, como nome, e-mail e dados da organização, bem como dados de uso coletados automaticamente através de cookies e tecnologias similares.</p>

    <h4>2. Uso das Informações</h4>
    <p>Utilizamos suas informações para fornecer, manter e melhorar nossos serviços, processar transações, enviar comunicações técnicas e responder aos seus comentários e perguntas.</p>

    <h4>3. Compartilhamento de Dados</h4>
    <p>Não vendemos seus dados pessoais. Podemos compartilhar informações com prestadores de serviços terceirizados que nos ajudam a operar nossa plataforma, sempre sob obrigações de confidencialidade.</p>
    
    <h4>4. Segurança de Dados</h4>
    <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados. Utilizamos criptografia SSL e servidores seguros (AWS).</p>

    <h4>5. Seus Direitos</h4>
    <p>Você tem o direito de acessar, corrigir ou excluir suas informações pessoais. Entre em contato conosco para exercer esses direitos.</p>

    <h4>6. LGPD</h4>
    <p>Nossa plataforma está em conformidade com a Lei Geral de Proteção de Dados (LGPD) do Brasil, garantindo transparência e controle sobre seus dados.</p>
    
    <h4>7. Contato</h4>
    <p>Para questões sobre privacidade, contate nosso Encarregado de Proteção de Dados (DPO) através do suporte.</p>
</div>
EOD;

        Page::updateOrCreate(
            ['slug' => 'privacidade'],
            [
                'title' => 'Política de Privacidade',
                'content' => $privacyContent
            ]
        );

        // 3. Sobre a Vivensi (Placeholder)
        $aboutContent = <<<'EOD'
<div class="legal-content">
    <h4>Sobre a Vivensi</h4>
    <p>A Vivensi é uma plataforma inovadora dedicada a simplificar a gestão de organizações do terceiro setor e projetos sociais.</p>

    <h4>Nossa Missão</h4>
    <p>Empoderar ONGs e gestores com ferramentas tecnológicas acessíveis, transparentes e eficientes.</p>
    
    <h4>Nossa Visão</h4>
    <p>Ser a referência nacional em transformação digital para o impacto social.</p>

    <h4>Nossos Valores</h4>
    <ul>
        <li>Transparência</li>
        <li>Inovação</li>
        <li>Compromisso Social</li>
        <li>Segurança</li>
    </ul>
</div>
EOD;

        Page::updateOrCreate(
            ['slug' => 'sobre'],
            [
                'title' => 'Quem Somos',
                'content' => $aboutContent
            ]
        );
    }
}
