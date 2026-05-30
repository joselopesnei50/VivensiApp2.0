@extends('layouts.app')
@section('title', 'Nova Campanha de E-mail')

@section('content')
<div style="margin-bottom:32px;">
    <a href="{{ route('manager.email_campaigns.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; color:#6366f1; font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
        <i class="fas fa-arrow-left"></i> Voltar às campanhas
    </a>
    <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:1.8rem; letter-spacing:-0.5px;">Nova Campanha de E-mail</h2>
    <p style="color:#64748b; margin:6px 0 0; font-size:0.9rem;">Salvo como rascunho — você dispara quando estiver pronto.</p>
</div>

@if($errors->any())
    <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:12px; padding:16px 20px; margin-bottom:24px; color:#991b1b;">
        <strong><i class="fas fa-circle-exclamation me-2"></i>Corrija os erros:</strong>
        <ul style="margin:8px 0 0 20px; font-size:0.88rem;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('manager.email_campaigns.store') }}" method="POST">
@csrf
<div class="row g-4">
    <div class="col-lg-8">

        {{-- Identificação --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px; margin-bottom:24px;">
            <h4 style="margin:0 0 24px; font-weight:900; color:#1e293b; font-size:1rem;">
                <i class="fas fa-tag me-2" style="color:#6366f1;"></i>Identificação
            </h4>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Nome interno da campanha *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="Ex: Newsletter Junho 2026 — Leads"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#f1f5f9'">
                <p style="color:#94a3b8; font-size:0.75rem; margin:6px 0 0;">Aparece só no painel, não é enviado.</p>
            </div>
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Assunto do e-mail *</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required
                       placeholder="Ex: 🚀 Novidades da empresa — Junho 2026"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#f1f5f9'">
            </div>
        </div>

        {{-- Remetente --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px; margin-bottom:24px;">
            <h4 style="margin:0 0 8px; font-weight:900; color:#1e293b; font-size:1rem;">
                <i class="fas fa-user-tie me-2" style="color:#6366f1;"></i>Remetente
            </h4>
            <p style="color:#64748b; font-size:0.82rem; margin:0 0 20px;">Deixe em branco para usar o remetente padrão do sistema.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Nome do remetente</label>
                    <input type="text" name="sender_name" value="{{ old('sender_name') }}"
                           placeholder="Nome da Empresa"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                </div>
                <div class="col-md-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">E-mail do remetente</label>
                    <input type="email" name="sender_email" value="{{ old('sender_email') }}"
                           placeholder="contato@suaempresa.com.br"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                </div>
                <div class="col-md-12">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">
                        E-mail de resposta (Reply-To)
                        <span style="font-weight:400; color:#94a3b8; font-size:0.78rem; margin-left:6px;">— aparece quando o destinatário clica em "Responder"</span>
                    </label>
                    <input type="email" name="reply_to_email" value="{{ old('reply_to_email') }}"
                           placeholder="comercial@suaempresa.com.br"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                </div>
            </div>
        </div>

        {{-- Conteúdo HTML --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <h4 style="margin:0; font-weight:900; color:#1e293b; font-size:1rem;">
                    <i class="fas fa-code me-2" style="color:#6366f1;"></i>Conteúdo HTML
                </h4>
                <div style="display:flex; gap:8px;">
                    <button type="button" onclick="togglePreview()"
                            style="padding:8px 16px; border-radius:10px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:0.8rem; cursor:pointer; color:#475569;">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                    <button type="button" onclick="insertTemplate()"
                            style="padding:8px 16px; border-radius:10px; border:none; background:#6366f1; color:white; font-weight:700; font-size:0.8rem; cursor:pointer;">
                        <i class="fas fa-magic me-1"></i>Inserir template
                    </button>
                </div>
            </div>
            <textarea name="html_content" id="htmlContent" required rows="20"
                      placeholder="Cole aqui o HTML completo do e-mail..."
                      style="width:100%; padding:16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.82rem; font-family:monospace; resize:vertical; box-sizing:border-box; line-height:1.6;">{{ old('html_content') }}</textarea>
            <div id="previewPane" style="display:none; margin-top:16px; border:2px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                <div style="background:#f8fafc; padding:10px 16px; font-size:0.78rem; font-weight:700; color:#64748b; border-bottom:1px solid #e2e8f0;">
                    <i class="fas fa-eye me-1"></i>PREVIEW
                </div>
                <iframe id="previewFrame" style="width:100%; height:500px; border:none;"></iframe>
            </div>
        </div>
    </div>

    <div class="col-lg-4">

        {{-- Público --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:20px;">
            <h4 style="margin:0 0 6px; font-weight:900; color:#1e293b; font-size:1rem;">
                <i class="fas fa-users me-2" style="color:#6366f1;"></i>Público-alvo *
            </h4>
            <p style="color:#64748b; font-size:0.78rem; margin:0 0 18px;">Selecione quem receberá este e-mail.</p>

            @php
                $audiences = [
                    'leads' => [
                        'icon'  => 'fa-user-plus',
                        'label' => 'Leads (Landing Pages)',
                        'desc'  => 'Contatos captados pelas suas páginas de captação',
                        'color' => '#d97706',
                        'bg'    => '#fffbeb',
                    ],
                    'manual' => [
                        'icon'  => 'fa-at',
                        'label' => 'Somente E-mails Avulsos',
                        'desc'  => 'Apenas os e-mails inseridos manualmente abaixo',
                        'color' => '#64748b',
                        'bg'    => '#f1f5f9',
                    ],
                ];
            @endphp

            @foreach($audiences as $val => $aud)
            @php $isSelected = old('audience_type', 'leads') === $val; @endphp
            <label id="lbl_{{ $val }}"
                   style="display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:12px; border:2px solid {{ $isSelected ? '#6366f1' : '#f1f5f9' }}; margin-bottom:10px; cursor:pointer;"
                   onclick="selectAudience(this, '{{ $val }}')">
                <input type="radio" name="audience_type" value="{{ $val }}" {{ $isSelected ? 'checked' : '' }}
                       style="margin-top:3px; accent-color:#6366f1;">
                <div>
                    <div style="font-weight:800; color:#1e293b; font-size:0.88rem; margin-bottom:3px;">
                        <span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:{{ $aud['bg'] }}; border-radius:6px; margin-right:6px; color:{{ $aud['color'] }};">
                            <i class="fas {{ $aud['icon'] }}" style="font-size:0.7rem;"></i>
                        </span>{{ $aud['label'] }}
                    </div>
                    <div style="color:#64748b; font-size:0.75rem; margin-left:28px;">{{ $aud['desc'] }}</div>
                </div>
            </label>
            @endforeach
        </div>

        {{-- Como funciona --}}
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:16px; padding:18px 20px; margin-bottom:20px;">
            <div style="font-weight:800; color:#1d4ed8; font-size:0.83rem; margin-bottom:10px;">
                <i class="fas fa-circle-info me-2"></i>Como funciona
            </div>
            <ol style="color:#1e40af; font-size:0.78rem; line-height:1.9; margin:0; padding-left:16px;">
                <li>Salve o rascunho</li>
                <li>Revise o preview do e-mail</li>
                <li>Clique em <strong>Disparar</strong> na lista</li>
                <li>O Brevo processa e envia</li>
                <li>Acompanhe as métricas aqui</li>
            </ol>
        </div>

        {{-- E-mails manuais --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:20px;">
            <h4 style="margin:0 0 6px; font-weight:900; color:#1e293b; font-size:1rem;">
                <i class="fas fa-at me-2" style="color:#6366f1;"></i>E-mails avulsos
            </h4>
            <p style="color:#64748b; font-size:0.78rem; margin:0 0 14px; line-height:1.6;">
                Adicione endereços extras além do público selecionado. Um por linha, vírgula ou ponto-e-vírgula.
            </p>
            <textarea name="manual_emails_raw" rows="5"
                      placeholder="joao@email.com&#10;Maria Silva <maria@email.com>"
                      style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.82rem; font-family:monospace; resize:vertical; box-sizing:border-box; line-height:1.6;"
                      onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#f1f5f9'"
                      oninput="countEmails(this)">{{ old('manual_emails_raw') }}</textarea>
            <div id="emailCount" style="color:#94a3b8; font-size:0.72rem; margin-top:6px; text-align:right;"></div>
        </div>

        {{-- Ações --}}
        <div class="vivensi-card" style="padding:24px; border-radius:20px;">
            <button type="submit"
                    style="width:100%; padding:16px; border:none; border-radius:12px; background:#6366f1; color:white; font-weight:800; font-size:0.95rem; cursor:pointer; margin-bottom:12px;">
                <i class="fas fa-save me-2"></i>Salvar Rascunho
            </button>
            <a href="{{ route('manager.email_campaigns.index') }}"
               style="display:block; text-align:center; padding:12px; border:2px solid #e2e8f0; border-radius:12px; color:#64748b; font-weight:700; font-size:0.88rem; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </div>
</div>
</form>

<script>
function selectAudience(label, val) {
    document.querySelectorAll('[id^="lbl_"]').forEach(l => l.style.borderColor = '#f1f5f9');
    label.style.borderColor = '#6366f1';
}
function togglePreview() {
    const pane = document.getElementById('previewPane');
    const frame = document.getElementById('previewFrame');
    if (pane.style.display === 'none') {
        pane.style.display = 'block';
        frame.srcdoc = document.getElementById('htmlContent').value || '<p style="font-family:sans-serif;color:#94a3b8;padding:40px;text-align:center;">Nenhum conteúdo ainda.</p>';
    } else { pane.style.display = 'none'; }
}
function countEmails(textarea) {
    const lines = textarea.value.split(/[\n,;]+/).map(l => l.trim()).filter(l => l.length > 0);
    const valid = lines.filter(l => { const m = l.match(/<([^>]+)>/); const e = m ? m[1] : l; return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); });
    const el = document.getElementById('emailCount');
    if (el) el.textContent = valid.length > 0 ? valid.length + ' e-mail(s) válido(s)' : '';
}
function insertTemplate() {
    const orgName = '{{ auth()->user()->tenant->name ?? "Empresa" }}';
    const tpl = `<!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${orgName}</title></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="background:linear-gradient(135deg,#4f46e5,#3730a3);padding:36px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:26px;font-weight:800;">${orgName}</h1>
  </td></tr>
  <tr><td style="padding:40px;">
    <h2 style="color:#0f172a;margin:0 0 16px;font-size:22px;font-weight:700;">Olá! 👋</h2>
    <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;">
      <!-- Escreva o conteúdo principal aqui -->
    </p>
    <div style="text-align:center;margin:30px 0;">
      <a href="#" style="background:#4f46e5;color:#fff;padding:14px 28px;text-decoration:none;border-radius:8px;font-weight:700;display:inline-block;">
        Saiba Mais
      </a>
    </div>
    <p style="color:#64748b;font-size:14px;line-height:1.7;margin:0;">
      Atenciosamente,<br><strong>${orgName}</strong>
    </p>
  </td></tr>
  <tr><td style="background:#f1f5f9;padding:24px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 8px;">&copy; {{ date('Y') }} ${orgName}. Todos os direitos reservados.</p>
    <p style="margin:0;">Para descadastrar-se, responda com o assunto "Descadastrar".</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>`;
    document.getElementById('htmlContent').value = tpl;
}
</script>
@endsection
