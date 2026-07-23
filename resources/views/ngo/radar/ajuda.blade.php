@extends('layouts.app')

@section('content')
<div style="max-width:820px;margin:0 auto;padding:24px 16px;">

    {{-- Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('ngo.radar.index') }}" style="color:#6366f1;font-size:0.78rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao Radar
        </a>

        <div style="display:flex;align-items:center;gap:16px;margin-top:14px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                 style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid #c4b5fd;box-shadow:0 0 20px rgba(99,102,241,0.2);flex-shrink:0;">
            <div>
                <h2 style="margin:0 0 3px;font-weight:950;font-size:1.5rem;letter-spacing:-0.5px;color:#1e293b;">
                    Guia do Radar de Editais
                </h2>
                <p style="color:#64748b;font-size:0.85rem;margin:0;">
                    Bruce IA explica como configurar o Radar para encontrar os melhores editais para a sua organização.
                </p>
            </div>
        </div>
    </div>

    {{-- Índice rápido --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:28px;">
        <div style="font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Neste guia</div>
        <div style="display:flex;flex-direction:column;gap:6px;">
            <a href="#o-que-e"     style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-satellite-dish me-2" style="width:16px;"></i>O que é o Radar de Editais</a>
            <a href="#ibge"        style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-map-marker-alt me-2" style="width:16px;"></i>Como encontrar o código IBGE</a>
            <a href="#areas"       style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-tags me-2" style="width:16px;"></i>Quais áreas de atuação informar</a>
            <a href="#score"       style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-chart-bar me-2" style="width:16px;"></i>O que é o score de relevância</a>
            <a href="#digest"      style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-envelope me-2" style="width:16px;"></i>Digest semanal (e-mail ou WhatsApp)</a>
            <a href="#feedback"    style="color:#6366f1;font-size:0.85rem;text-decoration:none;font-weight:600;"><i class="fas fa-thumbs-up me-2" style="width:16px;"></i>Por que dar feedback nos achados</a>
        </div>
    </div>

    {{-- Seção 1 --}}
    <div id="o-que-e" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-satellite-dish" style="color:#6366f1;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">O que é o Radar de Editais</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 10px;">O Radar monitora automaticamente duas fontes de editais públicos todos os dias às <strong>06h</strong>:</p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                <div style="display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:0.72rem;font-weight:700;background:#ede9fe;color:#4c1d95;padding:2px 8px;border-radius:6px;flex-shrink:0;margin-top:2px;">Querido Diário</span>
                    <span>Diários oficiais de municípios de todo o Brasil, com busca por palavras-chave como "chamamento público" e "termo de fomento".</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:0.72rem;font-weight:700;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:6px;flex-shrink:0;margin-top:2px;">Transferegov</span>
                    <span>Chamamentos públicos federais abertos para OSCs e ONGs, disponíveis em todo o território nacional.</span>
                </div>
            </div>
            <p style="margin:0;">O Bruce IA analisa cada achado, extrai o objeto, prazo e valor, e calcula o quanto ele é relevante para o <strong>perfil da sua organização</strong>. Você só vê o que importa.</p>
        </div>
    </div>

    {{-- Seção 2 --}}
    <div id="ibge" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#d1fae5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-map-marker-alt" style="color:#059669;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">Como encontrar o código IBGE</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 12px;">O código IBGE é um número de <strong>7 dígitos</strong> que identifica seu município. Ele é usado para filtrar editais do Querido Diário.</p>

            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:14px;">
                <div style="font-weight:700;color:#166534;margin-bottom:8px;font-size:0.82rem;"><i class="fas fa-lightbulb me-2"></i>Como encontrar o seu</div>
                <ol style="margin:0;padding-left:18px;color:#166534;font-size:0.82rem;line-height:1.8;">
                    <li>Acesse <strong>ibge.gov.br/cidades-e-estados</strong></li>
                    <li>Pesquise pelo nome da sua cidade</li>
                    <li>O código aparece na URL ou nos dados do município (ex: <code style="background:#dcfce7;padding:1px 5px;border-radius:4px;">3550308</code> para São Paulo)</li>
                </ol>
            </div>

            <div style="font-size:0.82rem;color:#64748b;">
                <strong>Capitais mais buscadas:</strong><br>
                São Paulo <code>3550308</code> · Rio de Janeiro <code>3304557</code> · Fortaleza <code>2304400</code> · Belo Horizonte <code>3106200</code> · Curitiba <code>4106902</code> · Manaus <code>1302603</code> · Brasília <code>5300108</code> · Recife <code>2611606</code> · Salvador <code>2927408</code> · Porto Alegre <code>4314902</code>
            </div>

            <div style="margin-top:12px;padding:10px 14px;background:#fef3c7;border-radius:8px;border:1px solid #fcd34d;font-size:0.82rem;color:#92400e;">
                <i class="fas fa-info-circle me-2"></i><strong>Dica:</strong> Se sua organização atende mais de um município, informe o município-sede. Editais federais do Transferegov aparecem independente do IBGE configurado.
            </div>
        </div>
    </div>

    {{-- Seção 3 --}}
    <div id="areas" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-tags" style="color:#d97706;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">Quais áreas de atuação informar</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 12px;">As áreas de atuação ensinam o Bruce IA a reconhecer editais relevantes para a sua causa. Informe <strong>uma área por linha</strong>, de forma simples e direta.</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px;margin-bottom:14px;">
                @foreach(['assistência social','criança e adolescente','idoso','pessoa com deficiência','mulher','juventude','saúde','educação','cultura','esporte','meio ambiente','habitação','segurança alimentar','geração de renda','direitos humanos','inclusão digital'] as $area)
                <span style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:8px;font-size:0.75rem;font-weight:600;">{{ $area }}</span>
                @endforeach
            </div>

            <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;font-size:0.8rem;color:#64748b;">
                <strong>Exemplo de preenchimento:</strong>
                <pre style="margin:8px 0 0;font-size:0.78rem;color:#374151;background:none;padding:0;line-height:1.8;">assistência social
criança e adolescente
idoso</pre>
            </div>

            <div style="margin-top:12px;padding:10px 14px;background:#ede9fe;border-radius:8px;border:1px solid #c4b5fd;font-size:0.82rem;color:#4c1d95;">
                <i class="fas fa-robot me-2"></i><strong>Como o Bruce usa isso:</strong> quando a IA analisa um edital e encontra correspondência entre as áreas do edital e as áreas da sua ONG, o score de relevância aumenta — você fica no topo da lista.
            </div>
        </div>
    </div>

    {{-- Seção 4 --}}
    <div id="score" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#e0f2fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-chart-bar" style="color:#0369a1;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">O que é o score de relevância</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 14px;">O score (0–100%) indica o quanto um edital é adequado para o seu perfil. Ele é calculado somando pontos por critério:</p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
                <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:1rem;font-weight:900;color:#10b981;min-width:40px;">+50</span>
                    <span>Edital publicado no município configurado (IBGE)</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:1rem;font-weight:900;color:#3b82f6;min-width:40px;">+20</span>
                    <span>Chamamento federal do Transferegov (vale para todas as ONGs)</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:1rem;font-weight:900;color:#6366f1;min-width:40px;">+15</span>
                    <span>A IA identificou no edital uma área que corresponde às suas áreas</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border-radius:8px;padding:10px 14px;">
                    <span style="font-size:1rem;font-weight:900;color:#f59e0b;min-width:40px;">+10</span>
                    <span>Palavra-chave da sua área aparece no texto do edital</span>
                </div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <span style="background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;">≥ 70% — Alta relevância</span>
                <span style="background:#fef3c7;color:#92400e;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;">40–69% — Relevante</span>
                <span style="background:#f1f5f9;color:#64748b;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;">< 40% — Baixa relevância</span>
            </div>
            <p style="margin:12px 0 0;font-size:0.82rem;color:#64748b;">O campo <strong>Score mínimo</strong> nas configurações filtra os editais exibidos — o padrão é 30. Aumente se quiser ver menos achados, mas mais precisos.</p>
        </div>
    </div>

    {{-- Seção 5 --}}
    <div id="digest" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#fce7f3;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-envelope" style="color:#be185d;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">Digest semanal (e-mail ou WhatsApp)</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 12px;">Todo <strong>segunda-feira às 08h</strong> o Radar envia um resumo dos melhores achados da semana, no canal que você escolher:</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                <div style="flex:1;min-width:160px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 14px;">
                    <div style="font-weight:700;color:#0369a1;margin-bottom:4px;font-size:0.82rem;"><i class="fas fa-envelope me-2"></i>E-mail</div>
                    <div style="font-size:0.8rem;color:#64748b;">Resumo formatado enviado para o e-mail cadastrado no perfil da organização.</div>
                </div>
                <div style="flex:1;min-width:160px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;">
                    <div style="font-weight:700;color:#15803d;margin-bottom:4px;font-size:0.82rem;"><i class="fab fa-whatsapp me-2"></i>WhatsApp</div>
                    <div style="font-size:0.8rem;color:#64748b;">Mensagem direta para o número da instância WhatsApp configurada.</div>
                </div>
                <div style="flex:1;min-width:160px;background:#fafafa;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;">
                    <div style="font-weight:700;color:#64748b;margin-bottom:4px;font-size:0.82rem;"><i class="fas fa-bell-slash me-2"></i>Desligado</div>
                    <div style="font-size:0.8rem;color:#94a3b8;">Nenhum envio automático. Acesse o Radar quando quiser.</div>
                </div>
            </div>
            <p style="margin:0;font-size:0.82rem;color:#64748b;">O digest envia no máximo 5 achados por vez, priorizados pelo score. Cada achado é enviado apenas uma vez.</p>
        </div>
    </div>

    {{-- Seção 6 --}}
    <div id="feedback" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:36px;height:36px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-thumbs-up" style="color:#16a34a;font-size:0.9rem;"></i>
            </div>
            <h3 style="margin:0;font-weight:900;font-size:1.05rem;color:#1e293b;">Por que dar feedback nos achados</h3>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;font-size:0.85rem;color:#374151;line-height:1.7;">
            <p style="margin:0 0 12px;">O feedback de <strong>Útil / Não útil</strong> que você dá em cada edital alimenta o sistema de aprendizado do Bruce IA. Com o tempo:</p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <i class="fas fa-check-circle" style="color:#10b981;margin-top:3px;flex-shrink:0;"></i>
                    <span>Keywords com alta taxa de "útil" passam a ser <strong>aprovadas automaticamente</strong> — sem precisar de curadoria manual.</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <i class="fas fa-check-circle" style="color:#10b981;margin-top:3px;flex-shrink:0;"></i>
                    <span>Keywords com muitos "não útil" são sinalizadas para revisão, melhorando a precisão do radar para todas as ONGs.</span>
                </div>
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <i class="fas fa-check-circle" style="color:#10b981;margin-top:3px;flex-shrink:0;"></i>
                    <span>Quanto mais feedbacks acumulados, mais inteligente e preciso fica o sistema.</span>
                </div>
            </div>
            <div style="background:#ede9fe;border-radius:8px;padding:12px 14px;font-size:0.82rem;color:#4c1d95;border:1px solid #c4b5fd;">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                     style="width:20px;height:20px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:6px;">
                <strong>Nota do Bruce IA:</strong> o mínimo para ativar a auto-aprovação de uma keyword é de 10 feedbacks com 70% de "útil". Cada avaliação sua conta!
            </div>
        </div>
    </div>

    {{-- CTA final --}}
    <div style="text-align:center;padding:28px;background:linear-gradient(135deg,#ede9fe,#e0f2fe);border-radius:14px;border:1px solid #c4b5fd;">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
             style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-bottom:10px;border:2px solid #a5b4fc;">
        <p style="font-weight:800;font-size:0.95rem;color:#3730a3;margin:0 0 6px;">Pronto para configurar?</p>
        <p style="font-size:0.82rem;color:#5b21b6;margin:0 0 16px;">Volte ao Radar e preencha o seu município e áreas de atuação.</p>
        <a href="{{ route('ngo.radar.index') }}"
           style="display:inline-flex;align-items:center;gap:8px;background:#6366f1;color:#fff;padding:10px 24px;border-radius:10px;font-weight:700;font-size:0.85rem;text-decoration:none;">
            <i class="fas fa-satellite-dish"></i>Ir para o Radar
        </a>
    </div>

</div>
@endsection
