@extends('layouts.app')
@section('title', 'Destine seu IR — Página')

@push('styles')
<style>
    .dir-hero { background: linear-gradient(135deg, #0b4f6c 0%, #145676 100%); border-radius: 18px; padding: 26px 30px; margin-bottom: 20px; color: #fff; }
    .dir-hero h1 { font-size: 1.5rem; font-weight: 800; margin: 0; }
    .dir-hero p { opacity: .85; margin: 6px 0 0; font-size: .95rem; }
    .dir-hero__preview { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; padding: 8px 14px; background: rgba(255,255,255,.14); border-radius: 10px; text-decoration: none; color: #fff; font-weight: 600; font-size: .88rem; }
    .dir-hero__preview:hover { background: rgba(255,255,255,.22); color: #fff; }

    .dir-grid { display: grid; grid-template-columns: 1fr; gap: 18px; }
    @media (min-width: 900px) { .dir-grid { grid-template-columns: 2fr 1fr; } }

    .dir-card { background: #fff; border-radius: 14px; padding: 22px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 18px; }
    .dir-card h2 { font-size: 1.1rem; font-weight: 700; margin: 0 0 14px; color: #0b4f6c; display: flex; align-items: center; gap: 10px; }
    .dir-card h2 i { color: #f5b027; }
    .dir-card label { display: block; font-weight: 600; color: #374151; font-size: .85rem; margin-bottom: 6px; }
    .dir-card input[type="text"], .dir-card input[type="email"], .dir-card input[type="date"], .dir-card input[type="number"], .dir-card textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; }
    .dir-card textarea { min-height: 90px; resize: vertical; }
    .dir-card input[type="file"] { padding: 6px 0; }
    .dir-row { margin-bottom: 14px; }
    .dir-row-2 { display: grid; grid-template-columns: 1fr; gap: 12px; }
    @media (min-width: 700px) { .dir-row-2 { grid-template-columns: 1fr 1fr; } }

    .dir-switch { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
    .dir-switch input { width: 18px; height: 18px; }
    .dir-switch label { margin: 0; }

    .dir-steps { border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px; margin-bottom: 10px; background: #f8fafc; position: relative; }
    .dir-steps__remove { position: absolute; top: 10px; right: 10px; background: #fef2f2; color: #991b1b; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: .78rem; font-weight: 700; }
    .dir-steps__add { background: #0b4f6c; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; cursor: pointer; font-weight: 700; font-size: .85rem; margin-top: 6px; }

    .dir-btn { background: #0b4f6c; color: #fff; border: none; border-radius: 10px; padding: 12px 22px; font-weight: 700; cursor: pointer; }
    .dir-btn:hover { background: #0a4660; }
    .dir-btn-ghost { background: #fff; border: 1.5px solid #0b4f6c; color: #0b4f6c; }

    .dir-flash { background: rgba(16,185,129,.08); border-left: 4px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 600; }

    .dir-media-preview { max-width: 100%; max-height: 160px; border-radius: 10px; margin-top: 8px; display: block; }

    .dir-pub-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 800; margin-left: 10px; }
    .dir-pub-pill--on { background: #d1fae5; color: #065f46; }
    .dir-pub-pill--off { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="dir-hero">
        <h1>
            <i class="fas fa-hand-holding-heart" style="color:#f5b027;"></i>
            Destine seu IR — Página pública
            @if($settings->is_published)
                <span class="dir-pub-pill dir-pub-pill--on">Publicada</span>
            @else
                <span class="dir-pub-pill dir-pub-pill--off">Rascunho</span>
            @endif
        </h1>
        <p>Edite textos, vídeo e lista das 25 entidades parceiras. A página fica em <code style="background:rgba(255,255,255,.18); padding:2px 6px; border-radius:4px;">{{ url('/destine-ir') }}</code></p>
        <a href="{{ url('/destine-ir') }}" target="_blank" class="dir-hero__preview">
            <i class="fas fa-external-link-alt"></i> Pré-visualizar página
        </a>
        <a href="{{ route('admin.destine-ir.metrics') }}" class="dir-hero__preview" style="margin-left:8px;">
            <i class="fas fa-chart-pie"></i> Ver métricas das entidades
        </a>
        <a href="{{ route('admin.destine-ir.signups') }}" class="dir-hero__preview" style="margin-left:8px;">
            <i class="fas fa-user-plus"></i> Inscrições na reunião
        </a>
    </div>

    @if(session('status'))
        <div class="dir-flash"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="dir-flash" style="background:rgba(220,38,38,.08); border-left-color:#dc2626; color:#7f1d1d;">
            <strong>Erros:</strong>
            <ul style="margin:6px 0 0 20px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="dir-grid">
        <div>
            <form method="POST" action="{{ route('admin.destine-ir.update') }}">
                @csrf
                @method('PUT')

                {{-- Status + toggles --}}
                <div class="dir-card">
                    <h2><i class="fas fa-toggle-on"></i> Status e seções visíveis</h2>
                    <div class="dir-switch">
                        <input type="checkbox" name="is_published" value="1" id="is_published" @checked($settings->is_published)>
                        <label for="is_published">Página publicada (fora do ar quando desmarcado)</label>
                    </div>
                    <div class="dir-switch">
                        <input type="checkbox" name="show_deadline_banner" value="1" id="show_deadline_banner" @checked($settings->show_deadline_banner)>
                        <label for="show_deadline_banner">Mostrar faixa com prazo final (31/12)</label>
                    </div>
                    <div class="dir-switch">
                        <input type="checkbox" name="show_simulator" value="1" id="show_simulator" @checked($settings->show_simulator)>
                        <label for="show_simulator">Mostrar simulador "quanto posso destinar"</label>
                    </div>
                    <div class="dir-switch">
                        <input type="checkbox" name="show_partners_wall" value="1" id="show_partners_wall" @checked($settings->show_partners_wall)>
                        <label for="show_partners_wall">Mostrar mural das entidades parceiras</label>
                    </div>
                    <div class="dir-switch">
                        <input type="checkbox" name="show_share_bar" value="1" id="show_share_bar" @checked($settings->show_share_bar)>
                        <label for="show_share_bar">Mostrar barra de compartilhar</label>
                    </div>
                    <div class="dir-switch">
                        <input type="checkbox" name="show_meeting_cta" value="1" id="show_meeting_cta" @checked($settings->show_meeting_cta)>
                        <label for="show_meeting_cta">Mostrar box de inscrição na reunião online</label>
                    </div>
                </div>

                {{-- Reunião online --}}
                <div class="dir-card">
                    <h2><i class="fas fa-video"></i> Reunião online (box de inscrição)</h2>
                    <p style="font-size:.84rem; color:#6b7280; margin:-6px 0 14px;">
                        Captura nome, e-mail e WhatsApp dos interessados pra depois você enviar o dia/link. Lista disponível em <a href="{{ route('admin.destine-ir.signups') }}" target="_blank">Inscrições na reunião</a>.
                    </p>
                    <div class="dir-row">
                        <label>Título</label>
                        <input type="text" name="meeting_title" value="{{ old('meeting_title', $settings->meeting_title) }}">
                    </div>
                    <div class="dir-row">
                        <label>Descrição</label>
                        <textarea name="meeting_description">{{ old('meeting_description', $settings->meeting_description) }}</textarea>
                    </div>
                </div>

                {{-- Hero --}}
                <div class="dir-card">
                    <h2><i class="fas fa-heading"></i> Topo da página</h2>
                    <div class="dir-row">
                        <label>Título</label>
                        <input type="text" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}" required>
                    </div>
                    <div class="dir-row">
                        <label>Subtítulo</label>
                        <input type="text" name="hero_subtitle" value="{{ old('hero_subtitle', $settings->hero_subtitle) }}" required>
                    </div>
                    <div class="dir-row-2">
                        <div>
                            <label>Faixa de prazo (texto)</label>
                            <input type="text" name="deadline_label" value="{{ old('deadline_label', $settings->deadline_label) }}">
                        </div>
                        <div>
                            <label>Data limite (pra contagem)</label>
                            <input type="date" name="deadline_date" value="{{ old('deadline_date', optional($settings->deadline_date)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                {{-- Textos --}}
                <div class="dir-card">
                    <h2><i class="fas fa-align-left"></i> O que é a destinação</h2>
                    <textarea name="what_is_text">{{ old('what_is_text', $settings->what_is_text) }}</textarea>
                </div>

                <div class="dir-card">
                    <h2><i class="fas fa-user-check"></i> Quem pode destinar</h2>
                    <textarea name="who_can_text">{{ old('who_can_text', $settings->who_can_text) }}</textarea>
                </div>

                {{-- Caminho 1 --}}
                <div class="dir-card">
                    <h2><i class="fas fa-route"></i> Caminho 1 — até 31/12 (até 6%)</h2>
                    <div id="path1-list">
                        @foreach(($settings->path1_steps ?? []) as $i => $step)
                            <div class="dir-steps">
                                <button type="button" class="dir-steps__remove" onclick="this.parentElement.remove()">Remover</button>
                                <label>Título do passo</label>
                                <input type="text" name="path1_steps[{{ $i }}][title]" value="{{ $step['title'] ?? '' }}">
                                <label style="margin-top:8px;">Texto</label>
                                <textarea name="path1_steps[{{ $i }}][text]">{{ $step['text'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="dir-steps__add" onclick="addStep('path1-list', 'path1_steps')"><i class="fas fa-plus"></i> Adicionar passo</button>
                </div>

                {{-- Caminho 2 --}}
                <div class="dir-card">
                    <h2><i class="fas fa-route"></i> Caminho 2 — na declaração do IR (até 3%)</h2>
                    <div id="path2-list">
                        @foreach(($settings->path2_steps ?? []) as $i => $step)
                            <div class="dir-steps">
                                <button type="button" class="dir-steps__remove" onclick="this.parentElement.remove()">Remover</button>
                                <label>Título do passo</label>
                                <input type="text" name="path2_steps[{{ $i }}][title]" value="{{ $step['title'] ?? '' }}">
                                <label style="margin-top:8px;">Texto</label>
                                <textarea name="path2_steps[{{ $i }}][text]">{{ $step['text'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="dir-steps__add" onclick="addStep('path2-list', 'path2_steps')"><i class="fas fa-plus"></i> Adicionar passo</button>
                </div>

                {{-- Custo + FAQ --}}
                <div class="dir-card">
                    <h2><i class="fas fa-dollar-sign"></i> Quanto custa</h2>
                    <textarea name="cost_text">{{ old('cost_text', $settings->cost_text) }}</textarea>
                </div>

                <div class="dir-card">
                    <h2><i class="fas fa-circle-question"></i> Perguntas frequentes</h2>
                    <div id="faq-list">
                        @foreach(($settings->faq_items ?? []) as $i => $item)
                            <div class="dir-steps">
                                <button type="button" class="dir-steps__remove" onclick="this.parentElement.remove()">Remover</button>
                                <label>Pergunta</label>
                                <input type="text" name="faq_items[{{ $i }}][q]" value="{{ $item['q'] ?? '' }}">
                                <label style="margin-top:8px;">Resposta</label>
                                <textarea name="faq_items[{{ $i }}][a]">{{ $item['a'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="dir-steps__add" onclick="addFaq()"><i class="fas fa-plus"></i> Adicionar pergunta</button>
                </div>

                {{-- Dados do fundo --}}
                <div class="dir-card">
                    <h2><i class="fas fa-building-columns"></i> Dados do fundo</h2>
                    <div class="dir-row">
                        <label>Nome do fundo</label>
                        <input type="text" name="fund_name" value="{{ old('fund_name', $settings->fund_name) }}" required>
                    </div>
                    <div class="dir-row-2">
                        <div>
                            <label>CNPJ (chave PIX)</label>
                            <input type="text" name="fund_cnpj" value="{{ old('fund_cnpj', $settings->fund_cnpj) }}" required>
                        </div>
                        <div>
                            <label>E-mail do conselho</label>
                            <input type="email" name="fund_email" value="{{ old('fund_email', $settings->fund_email) }}" required>
                        </div>
                    </div>
                    <div class="dir-row">
                        <label>Nome do conselho</label>
                        <input type="text" name="council_name" value="{{ old('council_name', $settings->council_name) }}" required>
                    </div>
                </div>

                {{-- Rodapé --}}
                <div class="dir-card">
                    <h2><i class="fas fa-signature"></i> Rodapé</h2>
                    <div class="dir-row">
                        <label>Linha de assinatura</label>
                        <input type="text" name="footer_tagline" value="{{ old('footer_tagline', $settings->footer_tagline) }}">
                    </div>
                    <div class="dir-row">
                        <label>Texto legal (ECA / Lei 14.692)</label>
                        <textarea name="footer_legal_text">{{ old('footer_legal_text', $settings->footer_legal_text) }}</textarea>
                    </div>
                </div>

                {{-- Vídeo YouTube --}}
                <div class="dir-card">
                    <h2><i class="fab fa-youtube" style="color:#ff0033;"></i> Vídeo via YouTube</h2>
                    <div class="dir-row">
                        <label>Link do YouTube (opcional — usado no lugar dos MP4 quando preenchido)</label>
                        <input type="url" name="video_youtube_url" value="{{ old('video_youtube_url', $settings->video_youtube_url) }}" placeholder="https://www.youtube.com/watch?v=...">
                        @if($settings->youtube_id)
                            <p style="margin-top:8px; font-size:.82rem; color:#16a34a;"><i class="fas fa-check-circle"></i> ID detectado: <code>{{ $settings->youtube_id }}</code> — preview no site vai usar este vídeo.</p>
                        @elseif($settings->video_youtube_url)
                            <p style="margin-top:8px; font-size:.82rem; color:#dc2626;"><i class="fas fa-triangle-exclamation"></i> Link não reconhecido. Use um link <code>youtube.com/watch?v=...</code> ou <code>youtu.be/...</code>.</p>
                        @endif
                        <p style="margin-top:6px; font-size:.78rem; color:#6b7280;">Formatos aceitos: youtube.com/watch, youtu.be, youtube.com/shorts, youtube.com/embed. Deixe vazio pra usar os uploads MP4.</p>
                    </div>
                </div>

                {{-- Open Graph --}}
                <div class="dir-card">
                    <h2><i class="fas fa-share-nodes"></i> Compartilhamento (link bonito no WhatsApp)</h2>
                    <div class="dir-row">
                        <label>Título do preview</label>
                        <input type="text" name="og_title" value="{{ old('og_title', $settings->og_title) }}">
                    </div>
                    <div class="dir-row">
                        <label>Descrição do preview</label>
                        <textarea name="og_description">{{ old('og_description', $settings->og_description) }}</textarea>
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-bottom:30px;">
                    <button type="submit" class="dir-btn"><i class="fas fa-save"></i> Salvar alterações</button>
                    <a href="{{ url('/destine-ir') }}" target="_blank" class="dir-btn dir-btn-ghost"><i class="fas fa-eye"></i> Pré-visualizar</a>
                </div>
            </form>
        </div>

        {{-- Coluna lateral: mídia + entidades --}}
        <div>
            {{-- Vídeo 9:16 --}}
            <div class="dir-card">
                <h2><i class="fas fa-mobile-alt"></i> Vídeo vertical (9:16)</h2>
                <form method="POST" action="{{ route('admin.destine-ir.video') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="orientation" value="vertical">
                    @if($settings->video_vertical_url)
                        <video src="{{ $settings->video_vertical_url }}" controls class="dir-media-preview" style="max-height:280px;"></video>
                    @endif
                    <input type="file" name="video" accept="video/mp4" required style="margin-top:8px;">
                    <button type="submit" class="dir-btn" style="margin-top:8px; padding:8px 14px;">Enviar</button>
                </form>
            </div>

            {{-- Vídeo 16:9 --}}
            <div class="dir-card">
                <h2><i class="fas fa-tv"></i> Vídeo horizontal (16:9)</h2>
                <form method="POST" action="{{ route('admin.destine-ir.video') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="orientation" value="horizontal">
                    @if($settings->video_horizontal_url)
                        <video src="{{ $settings->video_horizontal_url }}" controls class="dir-media-preview"></video>
                    @endif
                    <input type="file" name="video" accept="video/mp4" required style="margin-top:8px;">
                    <button type="submit" class="dir-btn" style="margin-top:8px; padding:8px 14px;">Enviar</button>
                </form>
            </div>

            {{-- Poster --}}
            <div class="dir-card">
                <h2><i class="fas fa-image"></i> Capa do vídeo</h2>
                <form method="POST" action="{{ route('admin.destine-ir.poster') }}" enctype="multipart/form-data">
                    @csrf
                    @if($settings->video_poster_url)
                        <img src="{{ $settings->video_poster_url }}" alt="poster" class="dir-media-preview">
                    @endif
                    <input type="file" name="poster" accept="image/*" required style="margin-top:8px;">
                    <button type="submit" class="dir-btn" style="margin-top:8px; padding:8px 14px;">Enviar</button>
                </form>
            </div>

            {{-- OG Image --}}
            <div class="dir-card">
                <h2><i class="fab fa-whatsapp" style="color:#25d366;"></i> Imagem do WhatsApp</h2>
                <form method="POST" action="{{ route('admin.destine-ir.og-image') }}" enctype="multipart/form-data">
                    @csrf
                    @if($settings->og_image_url)
                        <img src="{{ $settings->og_image_url }}" alt="og" class="dir-media-preview">
                    @endif
                    <input type="file" name="og_image" accept="image/*" required style="margin-top:8px;">
                    <button type="submit" class="dir-btn" style="margin-top:8px; padding:8px 14px;">Enviar</button>
                </form>
                <p style="font-size:.78rem; color:#6b7280; margin-top:8px;">Recomendado: 1200×630px.</p>
            </div>

            {{-- Entidades parceiras --}}
            <div class="dir-card">
                <h2><i class="fas fa-people-group"></i> Entidades parceiras <small style="font-weight:400; color:#6b7280;">({{ $partners->count() }})</small></h2>

                <form method="POST" action="{{ route('admin.destine-ir.partners.store') }}" enctype="multipart/form-data" style="padding-bottom:14px; border-bottom:1px dashed #e5e7eb; margin-bottom:14px;">
                    @csrf
                    <label>Nome da entidade</label>
                    <input type="text" name="name" required placeholder="Ex: Casa dos Pequeninos">
                    <div class="dir-row-2" style="margin-top:8px;">
                        <div>
                            <label>Contato</label>
                            <input type="text" name="contact_name" placeholder="Opcional">
                        </div>
                        <div>
                            <label>Telefone</label>
                            <input type="text" name="contact_phone" placeholder="(16) 9...">
                        </div>
                    </div>
                    <label style="margin-top:8px;">E-mail de contato</label>
                    <input type="email" name="contact_email" placeholder="opcional@entidade.org">
                    <label style="margin-top:8px;">Logo (quadrada, até 2MB)</label>
                    <input type="file" name="logo" accept="image/*">
                    <input type="hidden" name="is_active" value="1">
                    <button type="submit" class="dir-btn" style="margin-top:10px; padding:8px 14px;"><i class="fas fa-plus"></i> Adicionar</button>
                </form>

                @forelse($partners as $partner)
                    <details style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                        <summary style="cursor:pointer; display:flex; align-items:center; gap:10px; font-weight:600;">
                            @if($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="logo" style="width:32px; height:32px; object-fit:cover; border-radius:6px;">
                            @else
                                <div style="width:32px; height:32px; background:#e5e7eb; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; color:#9ca3af;">—</div>
                            @endif
                            <span>{{ $partner->name }}</span>
                            @unless($partner->is_active)<span style="font-size:.72rem; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:999px;">inativa</span>@endunless
                            <span style="margin-left:auto; font-size:.75rem; color:#6b7280;">{{ $partner->visits_count }} visitas</span>
                        </summary>
                        <div style="padding:10px 0 4px 42px;">
                            <form method="POST" action="{{ route('admin.destine-ir.partners.update', $partner) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <label>Nome</label>
                                <input type="text" name="name" value="{{ $partner->name }}" required>
                                <label style="margin-top:6px;">Slug <small style="color:#6b7280;">(trecho da URL)</small></label>
                                <input type="text" name="slug" value="{{ $partner->slug }}" required>
                                <p style="font-size:.75rem; color:#374151; margin:4px 0 10px;">Link: <code>{{ url('/destine-ir/' . $partner->slug) }}</code></p>
                                <div class="dir-row-2">
                                    <div>
                                        <label>Contato</label>
                                        <input type="text" name="contact_name" value="{{ $partner->contact_name }}">
                                    </div>
                                    <div>
                                        <label>Telefone</label>
                                        <input type="text" name="contact_phone" value="{{ $partner->contact_phone }}">
                                    </div>
                                </div>
                                <label style="margin-top:6px;">E-mail</label>
                                <input type="email" name="contact_email" value="{{ $partner->contact_email }}">
                                <div class="dir-row-2" style="margin-top:6px;">
                                    <div>
                                        <label>Ordem</label>
                                        <input type="number" name="sort_order" value="{{ $partner->sort_order }}" min="0" max="9999">
                                    </div>
                                    <div class="dir-switch" style="padding-top:22px;">
                                        <input type="checkbox" name="is_active" value="1" id="active-{{ $partner->id }}" @checked($partner->is_active)>
                                        <label for="active-{{ $partner->id }}">Ativa</label>
                                    </div>
                                </div>
                                <label style="margin-top:6px;">Trocar logo</label>
                                <input type="file" name="logo" accept="image/*">
                                <div style="display:flex; gap:8px; margin-top:10px;">
                                    <button type="submit" class="dir-btn" style="padding:6px 12px; font-size:.85rem;"><i class="fas fa-save"></i> Salvar</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.destine-ir.partners.destroy', $partner) }}" onsubmit="return confirm('Remover {{ $partner->name }}?')" style="margin-top:8px;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:#fef2f2; color:#991b1b; border:none; border-radius:6px; padding:5px 12px; font-weight:700; font-size:.78rem; cursor:pointer;"><i class="fas fa-trash"></i> Remover entidade</button>
                            </form>
                        </div>
                    </details>
                @empty
                    <p style="color:#6b7280; font-size:.9rem;">Nenhuma entidade cadastrada ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    const path1Count = {{ count($settings->path1_steps ?? []) }};
    const path2Count = {{ count($settings->path2_steps ?? []) }};
    const faqCount   = {{ count($settings->faq_items ?? []) }};
    let counts = { path1_steps: path1Count, path2_steps: path2Count, faq_items: faqCount };

    function addStep(containerId, name) {
        const idx = counts[name]++;
        const html = `
            <div class="dir-steps">
                <button type="button" class="dir-steps__remove" onclick="this.parentElement.remove()">Remover</button>
                <label>Título do passo</label>
                <input type="text" name="${name}[${idx}][title]">
                <label style="margin-top:8px;">Texto</label>
                <textarea name="${name}[${idx}][text]"></textarea>
            </div>`;
        document.getElementById(containerId).insertAdjacentHTML('beforeend', html);
    }

    function addFaq() {
        const idx = counts.faq_items++;
        const html = `
            <div class="dir-steps">
                <button type="button" class="dir-steps__remove" onclick="this.parentElement.remove()">Remover</button>
                <label>Pergunta</label>
                <input type="text" name="faq_items[${idx}][q]">
                <label style="margin-top:8px;">Resposta</label>
                <textarea name="faq_items[${idx}][a]"></textarea>
            </div>`;
        document.getElementById('faq-list').insertAdjacentHTML('beforeend', html);
    }
</script>
@endsection
