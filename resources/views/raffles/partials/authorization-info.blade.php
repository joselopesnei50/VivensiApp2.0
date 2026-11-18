{{-- Aviso sobre a autorização da rifa. $compact = versão curta (Identidade Visual). --}}
@php $compact = $compact ?? false; @endphp
<div class="raffle-auth-info" style="background: var(--vv-tint-warning, #fffbeb); border: 1px solid var(--vv-tint-warning-border, #fde68a); border-radius: 14px; padding: 14px 16px; color: var(--ds-text, #0f172a); font-size: .85rem; line-height: 1.55;">
    <div style="font-weight: 800; margin-bottom: 4px; display: flex; gap: 8px; align-items: center;">
        <i class="fas fa-scale-balanced" style="color: #d97706;"></i> Rifa precisa de autorização
    </div>
    <div style="color: var(--ds-text-muted, #475569);">
        Para vender números de sorteio, a organização precisa de autorização prévia do
        <strong>Ministério da Fazenda</strong>, pela <strong>Secretaria de Prêmios e Apostas (SPA)</strong>,
        e não da Receita Federal. O pedido é feito no sistema <strong>SCPC</strong>, antes de começar a divulgar.
        @unless($compact)
            A regra para organizações da sociedade civil está na Lei 13.019/2014 (art. 84-B) e na Portaria SECAP/ME nº 20.749/2020.
            Sorteio com venda de números sem autorização pode ser considerado loteria não autorizada.
        @endunless
    </div>
    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px 16px; font-weight: 700;">
        <a href="https://www.gov.br/pt-br/servicos/obter-autorizacao-para-atividades-de-distribuicao-gratuita-de-premios-a-titulo-de-propaganda-ou-de-captacao-de-poupanca-popular" target="_blank" rel="noopener" style="color: var(--vv-link, #4f46e5);">Como pedir no gov.br <i class="fas fa-arrow-up-right-from-square" style="font-size: .7rem;"></i></a>
        <a href="https://www.gov.br/fazenda/pt-br/composicao/orgaos/secretaria-de-premios-e-apostas" target="_blank" rel="noopener" style="color: var(--vv-link, #4f46e5);">Secretaria de Prêmios e Apostas <i class="fas fa-arrow-up-right-from-square" style="font-size: .7rem;"></i></a>
    </div>
    @unless($compact)
        <div style="margin-top: 8px; font-size: .78rem; color: var(--ds-text-faint, #64748b);">
            Com o certificado em mãos, informe o número abaixo: ele aparece na página pública da rifa e passa confiança a quem compra.
            Isto é uma orientação, não consultoria jurídica.
        </div>
    @endunless
</div>
