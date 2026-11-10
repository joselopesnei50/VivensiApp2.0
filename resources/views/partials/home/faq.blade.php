{{-- Como começar (passos reais, em ordem) + perguntas frequentes. --}}
<section class="hf" id="duvidas" aria-labelledby="hf-title">
    <div class="hf-inner">
        <div class="hf-start">
            <h2 class="hf-title" id="hf-title">Como começar</h2>
            <ol class="hf-steps">
                <li><strong>Crie a conta da organização.</strong> Leva poucos minutos.</li>
                <li><strong>Assine o contrato de adesão e escolha o plano.</strong> Tudo online, com pagamento por PIX.</li>
                <li><strong>Traga o que você já tem.</strong> A planilha de beneficiários entra de uma vez pela importação.</li>
            </ol>
            <a href="{{ route('register') }}" class="hf-btn">Criar minha conta</a>
            <a href="{{ route('booking.index') }}" class="hf-link">Prefiro agendar uma demonstração</a>
        </div>

        <div class="hf-faq">
            <h3 class="hf-faq-title">Perguntas frequentes</h3>
            <details>
                <summary>Quanto custa?</summary>
                <p>O valor depende do porte da organização. Fale com a gente pelo chat ou pelo WhatsApp e montamos o plano do tamanho da sua operação. Não há fidelidade: dá para cancelar quando quiser.</p>
            </details>
            <details>
                <summary>Preciso instalar alguma coisa?</summary>
                <p>Não. A Vivensi funciona no navegador do computador e do celular. A chamada das turmas, por exemplo, pode ser feita direto no celular do educador.</p>
            </details>
            <details>
                <summary>Consigo trazer os dados que já tenho?</summary>
                <p>Sim. A lista de beneficiários pode ser importada de uma planilha. Se a mesma família já existir, os dados são atualizados em vez de duplicados.</p>
            </details>
            <details>
                <summary>Como ficam os dados das famílias e a LGPD?</summary>
                <p>Os dados ficam em servidores no Brasil (AWS São Paulo), com acesso por senha e verificação em duas etapas. O sistema tem termo de consentimento LGPD para imprimir e registrar, e o CPF aparece protegido nas listas.</p>
            </details>
            <details>
                <summary>Serve para uma ONG pequena?</summary>
                <p>Serve. Você usa só os módulos de que precisa — por exemplo, beneficiários e turmas — e liga os outros quando a organização crescer.</p>
            </details>
            <details>
                <summary>Quem são o Bruno e o Bruce?</summary>
                <p>O Bruno é o atendente do site: tira dúvidas e marca conversas com a equipe. O Bruce é a inteligência artificial que trabalha dentro do painel da organização, lendo os dados para sugerir editais, apontar riscos e montar relatórios.</p>
            </details>
            <details>
                <summary>Como funciona o suporte?</summary>
                <p>Por WhatsApp e e-mail, com pessoas da equipe Vivensi.</p>
            </details>
        </div>
    </div>
</section>

<style>
.hf{padding:104px 6%;background:var(--ink,#080E1A);border-top:1px solid rgba(255,255,255,.06)}
.hf-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.3fr);gap:72px;align-items:start}
.hf-title{font-size:clamp(1.8rem,3vw,2.3rem);font-weight:800;letter-spacing:-.02em;margin:0 0 24px;color:#fff}
.hf-steps{margin:0 0 32px;padding:0;list-style:none;counter-reset:hf}
.hf-steps li{counter-increment:hf;position:relative;padding:0 0 22px 48px;color:rgba(255,255,255,.78);font-size:.95rem;line-height:1.6}
.hf-steps li::before{content:counter(hf);position:absolute;left:0;top:-2px;width:30px;height:30px;border-radius:50%;border:1px solid rgba(255,255,255,.3);color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center}
.hf-steps li strong{display:block;color:#fff;font-weight:700}
.hf-btn{display:inline-block;background:#fff;color:#0F172A;font-weight:700;font-size:.95rem;padding:14px 24px;border-radius:10px;text-decoration:none}
.hf-btn:hover{background:#E8EEFF}
.hf-link{display:block;margin-top:16px;color:rgba(255,255,255,.75);font-size:.9rem;text-decoration:underline;text-underline-offset:3px}
.hf-link:hover{color:#fff}
.hf-faq-title{font-size:1.05rem;font-weight:700;color:rgba(255,255,255,.62);margin:6px 0 10px}
.hf-faq details{border-bottom:1px solid rgba(255,255,255,.12)}
.hf-faq summary{list-style:none;cursor:pointer;padding:18px 36px 18px 0;position:relative;font-weight:600;font-size:1rem;color:#fff}
.hf-faq summary::-webkit-details-marker{display:none}
.hf-faq summary::after{content:'+';position:absolute;right:4px;top:50%;transform:translateY(-50%);font-size:1.3rem;font-weight:400;color:rgba(255,255,255,.6)}
.hf-faq details[open] summary::after{content:'−'}
.hf-faq summary:focus-visible{outline:2px solid #5B82FF;outline-offset:2px;border-radius:4px}
.hf-faq details p{margin:0 0 20px;color:rgba(255,255,255,.78);font-size:.93rem;line-height:1.7;max-width:62ch}
@media (max-width:900px){.hf{padding:72px 16px}.hf-inner{grid-template-columns:1fr;gap:48px}}
</style>
