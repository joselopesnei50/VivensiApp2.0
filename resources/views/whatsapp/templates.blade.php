@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Omnichannel Oficial</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Modelos de Mensagem (Templates)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Visualize os modelos aprovados pela Meta para campanhas ativas e disparos em massa.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="vivensi-card" style="padding: 25px; border-top: 4px solid #1877f2;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-layer-group me-2" style="color: #1877f2;"></i> Templates Sincronizados
                </h4>
                
                <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" class="btn btn-outline-primary fw-bold" style="border-radius: 8px;">
                    <i class="fas fa-external-link-alt me-2"></i> Criar/Editar na Meta
                </a>
            </div>

            @if(isset($error))
                <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; border-radius: 12px; padding: 20px;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3" style="color: #f59e0b;"></i>
                    <h5 style="font-weight: 700;">Atenção</h5>
                    <p style="margin: 0; font-size: 0.9rem;">{{ $error }}</p>
                    <a href="{{ url('/whatsapp/settings') }}" class="btn btn-warning mt-3 fw-bold" style="border-radius: 8px;">Ir para Configurações</a>
                </div>
            @else
                
                @if(count($templates) > 0)
                    <div class="row g-4">
                        @foreach($templates as $tpl)
                            <div class="col-md-4">
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; height: 100%; display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                        <h5 style="margin: 0; font-size: 1rem; color: #1e293b; font-weight: 700; word-break: break-all;">
                                            {{ $tpl['name'] }}
                                        </h5>
                                        @if(($tpl['status'] ?? '') === 'APPROVED')
                                            <span class="badge bg-success" style="border-radius: 20px; padding: 5px 10px;">Aprovado</span>
                                        @elseif(($tpl['status'] ?? '') === 'REJECTED')
                                            <span class="badge bg-danger" style="border-radius: 20px; padding: 5px 10px;">Rejeitado</span>
                                        @else
                                            <span class="badge bg-warning text-dark" style="border-radius: 20px; padding: 5px 10px;">Pendente</span>
                                        @endif
                                    </div>
                                    
                                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 15px; font-weight: 600;">
                                        <span class="me-3"><i class="fas fa-globe"></i> Idioma: {{ $tpl['language'] ?? 'pt_BR' }}</span>
                                        <span><i class="fas fa-tag"></i> Categoria: {{ $tpl['category'] ?? 'MARKETING' }}</span>
                                    </div>

                                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; font-size: 0.85rem; color: #475569; flex-grow: 1;">
                                        @php
                                            $bodyText = 'Sem conteúdo de texto';
                                            if(isset($tpl['components'])) {
                                                foreach($tpl['components'] as $comp) {
                                                    if($comp['type'] === 'BODY') {
                                                        $bodyText = $comp['text'] ?? '';
                                                    }
                                                }
                                            }
                                        @endphp
                                        {!! nl2br(e($bodyText)) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 50px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px;">
                        <i class="fas fa-folder-open fa-3x" style="color: #94a3b8; margin-bottom: 15px;"></i>
                        <h5 style="color: #475569; font-weight: 700;">Nenhum Template Encontrado</h5>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">Você ainda não possui modelos aprovados na sua conta do Facebook Business.</p>
                        <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" class="btn btn-primary fw-bold" style="border-radius: 8px;">
                            <i class="fas fa-plus me-2"></i> Criar Meu Primeiro Template
                        </a>
                    </div>
                @endif

            @endif
            
            <div class="alert alert-info mt-4" style="font-size: 0.85rem; border-radius: 12px; border: none; background: #eff6ff; color: #1d4ed8;">
                <i class="fas fa-info-circle me-1"></i> <b>Nota:</b> Templates são modelos de mensagem aprovados manualmente pela Meta. Eles são os únicos conteúdos permitidos para iniciar uma conversa com um cliente de forma ativa (disparos em massa fora da janela de 24h).
            </div>
        </div>
    </div>
</div>
@endsection
