@extends('layouts.academy')

@section('title', 'Meus Certificados')

@section('content')
<div style="background-color: #0f172a; min-height: 100vh; padding: 40px 20px 80px; margin: -1.5rem;">

    <div class="container" style="max-width: 1100px; margin: 0 auto;">

        {{-- ─── Cabeçalho ────────────────────────────────────────────────── --}}
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
            <div>
                <div style="color:#818cf8; font-size:.75rem; font-weight:700; letter-spacing:3px; text-transform:uppercase; margin-bottom:8px;">
                    <i class="fas fa-award"></i> Vivensi Academy
                </div>
                <h1 style="color:#fff; font-weight:800; font-size:2.1rem; letter-spacing:-1px; margin:0;">
                    Meus Certificados
                </h1>
                <p style="color:#94a3b8; margin:8px 0 0; font-size:.95rem;">
                    {{ $certificates->count() }}
                    {{ $certificates->count() === 1 ? 'certificado emitido' : 'certificados emitidos' }}
                    em seu nome.
                </p>
            </div>

            <a href="{{ route('academy.index') }}"
               style="background: rgba(99,102,241,.14); border:1px solid rgba(99,102,241,.35); color:#a5b4fc;
                      padding:10px 18px; border-radius:10px; text-decoration:none; font-weight:600; font-size:.9rem;
                      display:inline-flex; align-items:center; gap:8px;">
                <i class="fas fa-arrow-left" style="font-size:.75rem;"></i> Voltar aos cursos
            </a>
        </div>

        {{-- ─── Lista ───────────────────────────────────────────────────── --}}
        @if($certificates->isEmpty())
            <div style="background:#131929; border:1px solid rgba(99,102,241,.15); border-radius:16px;
                        padding:60px 24px; text-align:center;">
                <div style="width:78px; height:78px; margin:0 auto 20px; border-radius:50%;
                            background:rgba(99,102,241,.12); display:inline-flex; align-items:center;
                            justify-content:center; color:#818cf8; font-size:1.8rem;">
                    <i class="fas fa-award"></i>
                </div>
                <h3 style="color:#e2e8f0; font-weight:700; margin:0 0 8px;">Nenhum certificado ainda</h3>
                <p style="color:#94a3b8; max-width:460px; margin:0 auto 22px; font-size:.95rem;">
                    Assim que você concluir 100% de um curso, o certificado oficial da
                    Vivensi Academy aparece aqui e fica disponível para download.
                </p>
                <a href="{{ route('academy.index') }}"
                   style="background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; padding:11px 22px;
                          border-radius:10px; text-decoration:none; font-weight:700; font-size:.9rem;
                          display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-play-circle"></i> Explorar cursos
                </a>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">

                @foreach($certificates as $cert)
                    @php
                        $c = $cert->course;
                        $codeShort = strtoupper(substr($cert->code, 0, 4)) . '&nbsp;•&nbsp;' . strtoupper(substr($cert->code, -4));
                    @endphp

                    <div style="background:#131929; border:1px solid rgba(99,102,241,.15); border-radius:16px;
                                overflow:hidden; display:flex; flex-direction:column;">

                        {{-- Banner do curso --}}
                        <div style="position:relative; padding-top:42%;
                                    background:linear-gradient(135deg,#1e1b4b,#312e81);">
                            @if($c && $c->thumbnail_url)
                                <img loading="lazy" src="{{ $c->thumbnail_url }}"
                                     alt="{{ $c->title }}"
                                     style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.65;">
                            @endif
                            <div style="position:absolute; inset:0;
                                        background:linear-gradient(180deg, rgba(15,23,42,.1) 40%, rgba(19,25,41,.98));"></div>

                            <div style="position:absolute; top:14px; left:14px; background:rgba(99,102,241,.22);
                                        border:1px solid rgba(165,180,252,.35); color:#c7d2fe; font-size:.7rem;
                                        font-weight:700; letter-spacing:1.5px; padding:5px 10px; border-radius:20px;
                                        text-transform:uppercase;">
                                <i class="fas fa-check-circle"></i> Concluído
                            </div>

                            <div style="position:absolute; top:14px; right:14px; width:38px; height:38px;
                                        border-radius:50%; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15);
                                        display:flex; align-items:center; justify-content:center; color:#fbbf24;">
                                <i class="fas fa-award"></i>
                            </div>

                            <div style="position:absolute; bottom:12px; left:14px; right:14px;">
                                <h3 style="color:#fff; font-weight:700; font-size:1.05rem; margin:0; line-height:1.35;
                                           display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
                                           overflow:hidden;">
                                    {{ $c->title ?? 'Curso removido' }}
                                </h3>
                            </div>
                        </div>

                        {{-- Corpo --}}
                        <div style="padding:18px 18px 20px; display:flex; flex-direction:column; gap:14px; flex:1;">

                            <div style="display:flex; justify-content:space-between; gap:10px; font-size:.78rem; color:#94a3b8;">
                                <div>
                                    <div style="color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:1.2px; font-size:.65rem; margin-bottom:2px;">
                                        Emitido em
                                    </div>
                                    <div style="color:#e2e8f0; font-weight:600;">
                                        {{ $cert->issued_at->format('d/m/Y') }}
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:1.2px; font-size:.65rem; margin-bottom:2px;">
                                        Instrutor
                                    </div>
                                    <div style="color:#e2e8f0; font-weight:600;">
                                        {{ $c->teacher_name ?? '—' }}
                                    </div>
                                </div>
                            </div>

                            <div style="background:#0f172a; border:1px dashed rgba(99,102,241,.28); border-radius:10px;
                                        padding:10px 12px;">
                                <div style="color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:1.5px; font-size:.62rem; margin-bottom:3px;">
                                    Código de validação
                                </div>
                                <div style="color:#a5b4fc; font-family:'Courier New', monospace; font-weight:700;
                                            font-size:.95rem; letter-spacing:2px;">
                                    {!! $codeShort !!}
                                </div>
                            </div>

                            <div style="display:flex; gap:8px; margin-top:auto;">
                                <a href="{{ route('academy.certificate.download', $cert->code) }}"
                                   style="flex:1; background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                          color:#fff; padding:10px 12px; border-radius:10px; text-decoration:none;
                                          font-weight:700; font-size:.85rem; text-align:center;
                                          display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                                    <i class="fas fa-file-arrow-down"></i> Baixar PDF
                                </a>
                                @if($c)
                                    <a href="{{ route('academy.show', $c->slug) }}"
                                       title="Ver curso"
                                       style="width:42px; background:rgba(99,102,241,.12); border:1px solid rgba(99,102,241,.25);
                                              color:#a5b4fc; border-radius:10px; text-decoration:none;
                                              display:inline-flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>
        @endif

    </div>

</div>
@endsection
