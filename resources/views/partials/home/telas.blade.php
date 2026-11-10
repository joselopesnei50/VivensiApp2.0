{{-- Telas reais do sistema (dados de exemplo), com uma anotação por tela.
     A tela só troca quando a pessoa escolhe um momento — sem rotação automática. --}}
@php
    $momentos = [
        ['id' => 'faltas', 'titulo' => 'Ver quem está faltando',
         'texto' => 'Cada aluno com o seu percentual de presença. Quem falta três vezes seguidas sobe para o topo, para a equipe falar com a família a tempo.',
         'img' => 'turma-frequencia', 'alt' => 'Tela da turma de violão com a presença de cada aluno e o alerta de risco de evasão',
         'nota' => '63% de presença: risco de evasão', 'x' => 37.6, 'y' => 47.3],
        ['id' => 'familias', 'titulo' => 'Acompanhar cada família',
         'texto' => 'Cadastro, atendimentos e histórico de cada família. A busca encontra por nome, NIS ou telefone, e o CPF aparece protegido na lista.',
         'img' => 'beneficiarios', 'alt' => 'Lista de beneficiários com busca, filtros e CPF mascarado',
         'nota' => 'CPF protegido na lista', 'x' => 52, 'y' => 87],
        ['id' => 'relatorio', 'titulo' => 'Fechar o relatório do ano',
         'texto' => 'Atendimentos do ano, perfil do público por gênero, idade e cor/raça e o gráfico mês a mês. Sai em PDF, pronto para o financiador.',
         'img' => 'relatorio-anual', 'alt' => 'Relatório anual com o perfil do público atendido e atendimentos por mês',
         'nota' => 'Perfil do público em um clique', 'x' => 21.5, 'y' => 27, 'lado' => 'esq'],
        ['id' => 'transparencia', 'titulo' => 'Prestar contas à comunidade',
         'texto' => 'Portal da Transparência público, com arrecadação, investimento social e dados abertos. Ele se atualiza sozinho com o que a equipe lança.',
         'img' => 'portal-transparencia', 'alt' => 'Portal da Transparência de uma organização com arrecadação, investimento social e resultado do ano',
         'nota' => 'Atualizado com o que a equipe lança', 'x' => 62, 'y' => 40],
    ];
@endphp
<section class="pd" id="por-dentro" aria-labelledby="pd-title">
    <div class="pd-inner">
        <div class="pd-side">
            <h2 class="pd-title" id="pd-title">A rotina da sua ONG, dentro da Vivensi</h2>
            <p class="pd-sub">Telas reais do sistema, com dados de exemplo.</p>

            <div class="pd-list" role="tablist" aria-label="Momentos da rotina">
                @foreach($momentos as $i => $m)
                    <button type="button" class="pd-item" role="tab" id="pd-tab-{{ $m['id'] }}"
                            aria-controls="pd-panel" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? '0' : '-1' }}"
                            data-i="{{ $i }}">
                        <span class="pd-item-t">{{ $m['titulo'] }}</span>
                        <span class="pd-item-d">{{ $m['texto'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="pd-stage" id="pd-panel" role="tabpanel" aria-labelledby="pd-tab-{{ $momentos[0]['id'] }}">
            <div class="pd-window">
                @foreach($momentos as $i => $m)
                    <figure class="pd-shot {{ $i === 0 ? 'is-on' : '' }}" data-i="{{ $i }}" @if($i !== 0) hidden @endif>
                        <img src="{{ asset('img/home/telas/' . $m['img'] . '.webp') }}"
                             srcset="{{ asset('img/home/telas/' . $m['img'] . '-800.webp') }} 800w, {{ asset('img/home/telas/' . $m['img'] . '.webp') }} 1600w"
                             sizes="(max-width: 900px) 100vw, 760px"
                             width="1600" height="1000" alt="{{ $m['alt'] }}" {{ $i === 0 ? '' : 'loading=lazy' }} decoding="async">
                        <span class="pd-note {{ ($m['lado'] ?? '') === 'esq' ? 'pd-note--esq' : '' }}" style="left:{{ $m['x'] }}%;top:{{ $m['y'] }}%">
                            <span class="pd-pin" aria-hidden="true"></span>
                            <span class="pd-note-t">{{ $m['nota'] }}</span>
                        </span>
                        <figcaption class="pd-cap">{{ $m['nota'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </div>
</section>

<style>
.pd{padding:112px 6% 104px;background:#0B1324;border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06)}
.pd-inner{max-width:1240px;margin:0 auto;display:grid;grid-template-columns:minmax(0,.86fr) minmax(0,1.6fr);gap:56px;align-items:center}
.pd-side,.pd-stage{min-width:0}
.pd-title{font-size:clamp(1.9rem,3.4vw,2.6rem);font-weight:800;line-height:1.12;letter-spacing:-.02em;margin:0 0 12px;color:#fff;max-width:14ch}
.pd-sub{font-size:.95rem;color:rgba(255,255,255,.62);margin:0 0 32px}
.pd-list{display:flex;flex-direction:column;border-left:1px solid rgba(255,255,255,.12)}
.pd-item{all:unset;box-sizing:border-box;display:block;cursor:pointer;padding:14px 0 14px 22px;margin-left:-1px;border-left:2px solid transparent;transition:border-color .2s,background-color .2s}
.pd-item-t{display:block;font-size:1.02rem;font-weight:700;color:rgba(255,255,255,.62);line-height:1.35}
.pd-item-d{display:none;font-size:.9rem;line-height:1.6;color:rgba(255,255,255,.78);margin-top:8px;max-width:42ch}
.pd-item[aria-selected="true"]{border-left-color:#5B82FF}
.pd-item[aria-selected="true"] .pd-item-t{color:#fff}
.pd-item[aria-selected="true"] .pd-item-d{display:block}
.pd-item:hover .pd-item-t{color:#fff}
.pd-item:focus-visible{outline:2px solid #5B82FF;outline-offset:4px;border-radius:4px}

.pd-window{position:relative;border-radius:14px;background:#fff;padding:6px;box-shadow:0 1px 0 rgba(255,255,255,.08) inset,0 40px 80px -20px rgba(0,0,0,.65)}
.pd-shot{margin:0;position:relative}
.pd-shot img{display:block;width:100%;max-width:100%;height:auto;border-radius:9px}
.pd-shot.is-on{animation:pdIn .28s ease-out}
@keyframes pdIn{from{opacity:.25}to{opacity:1}}
.pd-note{position:absolute;display:flex;align-items:center;gap:10px;transform:translate(-9px,-9px);pointer-events:none}
.pd-pin{flex:none;width:18px;height:18px;border-radius:50%;background:#3B6CF6;box-shadow:0 0 0 4px #fff,0 0 0 5px rgba(15,23,42,.18)}
.pd-note-t{background:#0F172A;color:#fff;font-size:.8rem;font-weight:600;line-height:1;padding:9px 12px;border-radius:8px;white-space:nowrap;box-shadow:0 10px 24px -8px rgba(15,23,42,.55)}
.pd-note--esq{flex-direction:row-reverse;transform:translate(calc(-100% + 9px),-9px)}
.pd-cap{display:none}

@media (max-width:900px){
  .pd{padding:72px 16px}
  .pd-inner{grid-template-columns:1fr;gap:28px}
  .pd-title{max-width:none}
  .pd-sub{margin-bottom:20px}
  .pd-list{flex-direction:row;overflow-x:auto;border-left:0;gap:8px;scroll-snap-type:x mandatory;padding-bottom:4px;-webkit-overflow-scrolling:touch}
  .pd-item{flex:none;scroll-snap-align:start;padding:10px 14px;margin:0;border:1px solid rgba(255,255,255,.16);border-radius:999px}
  .pd-item-t{font-size:.88rem;white-space:nowrap}
  .pd-item[aria-selected="true"]{background:#fff;border-color:#fff}
  .pd-item[aria-selected="true"] .pd-item-t{color:#0F172A}
  .pd-item .pd-item-d{display:none!important}
  .pd-note{display:none}
  .pd-window{padding:4px;border-radius:10px}
  .pd-cap{display:block;margin-top:12px;font-size:.92rem;line-height:1.55;color:rgba(255,255,255,.82)}
}
@media (prefers-reduced-motion:reduce){.pd-shot.is-on{animation:none}.pd-item{transition:none}}
</style>

<script>
(function () {
    var tabs = [].slice.call(document.querySelectorAll('#por-dentro .pd-item'));
    var shots = [].slice.call(document.querySelectorAll('#por-dentro .pd-shot'));
    var panel = document.getElementById('pd-panel');
    var textos = tabs.map(function (t) { return t.querySelector('.pd-item-d').textContent; });
    function show(i, focus) {
        tabs.forEach(function (t, k) {
            var on = k === i;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
        });
        shots.forEach(function (s, k) {
            var on = k === i;
            s.hidden = !on;
            s.classList.toggle('is-on', on);
            // no celular, a legenda embaixo da tela traz o texto do momento
            if (on) s.querySelector('.pd-cap').textContent = textos[k];
        });
        panel.setAttribute('aria-labelledby', tabs[i].id);
        if (focus) { tabs[i].focus(); tabs[i].scrollIntoView({block: 'nearest', inline: 'nearest'}); }
    }
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { show(i, false); });
        t.addEventListener('keydown', function (e) {
            var k = e.key, n = tabs.length;
            if (k === 'ArrowDown' || k === 'ArrowRight') { e.preventDefault(); show((i + 1) % n, true); }
            if (k === 'ArrowUp' || k === 'ArrowLeft') { e.preventDefault(); show((i - 1 + n) % n, true); }
        });
    });
    show(0, false);
})();
</script>
