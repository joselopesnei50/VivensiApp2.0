@extends('layouts.app')
@section('title', 'Nova Campanha de E-mail')

@section('content')
<div class="header-page" style="margin-bottom:32px;">
    <div>
        <a href="{{ route('admin.email_campaigns.index') }}"
           style="display:inline-flex; align-items:center; gap:6px; color:#6366f1; font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
            <i class="fas fa-arrow-left"></i> Voltar às campanhas
        </a>
        <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">Nova Campanha</h2>
        <p style="color:#64748b; margin:6px 0 0; font-size:0.95rem;">A campanha é salva como rascunho. Você dispara quando estiver pronto.</p>
    </div>
</div>

@if($errors->any())
    <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:12px; padding:16px 20px; margin-bottom:24px; color:#991b1b;">
        <strong><i class="fas fa-circle-exclamation me-2"></i>Corrija os erros:</strong>
        <ul style="margin:8px 0 0 20px; font-size:0.88rem;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.email_campaigns.store') }}" method="POST">
@csrf
<div class="row g-4">

    {{-- Coluna principal --}}
    <div class="col-lg-8">

        {{-- Identificação --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px; margin-bottom:24px;">
            <h4 style="margin:0 0 24px; font-weight:900; color:#1e293b; font-size:1.05rem;">
                <i class="fas fa-tag me-2" style="color:#6366f1;"></i>Identificação
            </h4>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Nome interno da campanha *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="Ex: Newsletter Maio 2026 — Clientes NGO"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#f1f5f9'">
                <p style="color:#94a3b8; font-size:0.75rem; margin:6px 0 0;">Aparece só no painel, não é enviado.</p>
            </div>
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Assunto do e-mail *</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required
                       placeholder="Ex: 🚀 Novidades Vivensi — Maio 2026"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box; transition:border-color 0.2s;"
                       onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#f1f5f9'">
                <p style="color:#94a3b8; font-size:0.75rem; margin:6px 0 0;">Linha de assunto que o destinatário vê na caixa de entrada.</p>
            </div>
        </div>

        {{-- Remetente --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px; margin-bottom:24px;">
            <h4 style="margin:0 0 8px; font-weight:900; color:#1e293b; font-size:1.05rem;">
                <i class="fas fa-user-tie me-2" style="color:#6366f1;"></i>Remetente
            </h4>
            <p style="color:#64748b; font-size:0.82rem; margin:0 0 20px;">Deixe em branco para usar o remetente padrão configurado nas configurações globais.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Nome do remetente</label>
                    <input type="text" name="sender_name" value="{{ old('sender_name') }}"
                           placeholder="Vivensi"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                </div>
                <div class="col-md-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">E-mail do remetente</label>
                    <input type="email" name="sender_email" value="{{ old('sender_email') }}"
                           placeholder="contato@vivensi.com.br"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                </div>
                <div class="col-md-12">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">
                        E-mail de resposta (Reply-To)
                        <span style="font-weight:400; color:#94a3b8; font-size:0.78rem; margin-left:6px;">— e-mail institucional que aparece quando o destinatário clica em "Responder"</span>
                    </label>
                    <input type="email" name="reply_to_email" value="{{ old('reply_to_email') }}"
                           placeholder="contato@suaorganizacao.org.br"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;">
                    <p style="color:#94a3b8; font-size:0.75rem; margin:6px 0 0;">Deixe em branco para que as respostas vão para o e-mail do remetente.</p>
                </div>
            </div>
        </div>

        {{-- Conteúdo --}}
        <div class="vivensi-card" style="padding:32px; border-radius:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <h4 style="margin:0; font-weight:900; color:#1e293b; font-size:1.05rem;">
                    <i class="fas fa-code me-2" style="color:#6366f1;"></i>Conteúdo HTML
                </h4>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" onclick="togglePreview()"
                            style="padding:8px 16px; border-radius:10px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:0.8rem; cursor:pointer; color:#475569;">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                    <button type="button" onclick="document.getElementById('emailImgUpload').click()"
                            style="padding:8px 16px; border-radius:10px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:0.8rem; cursor:pointer; color:#475569;">
                        <i class="fas fa-image me-1"></i>Inserir imagem
                    </button>
                    <input type="file" id="emailImgUpload" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
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

            {{-- Bruce IA — analise pre-envio (F1) --}}
            <div style="margin-top:20px; padding:20px; background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); border-radius:14px; border:2px solid #dbeafe;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div>
                        <strong style="color:#1e40af; font-size:.95rem;">🤖 Bruce analisa antes de enviar</strong>
                        <div style="font-size:.78rem; color:#64748b; margin-top:2px;">
                            Score anti-spam + sugestoes contextuais
                        </div>
                    </div>
                    <button type="button" id="btnBruceAnalyze"
                            style="padding:8px 16px; background:#3b82f6; color:#fff; border:0; border-radius:8px; font-weight:600; cursor:pointer;">
                        Analisar
                    </button>
                </div>
                <div id="bruceAnalyzeResult" style="display:none; margin-top:12px;"></div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Público --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:20px;">
            <h4 style="margin:0 0 20px; font-weight:900; color:#1e293b; font-size:1.05rem;">
                <i class="fas fa-users me-2" style="color:#6366f1;"></i>Público-alvo *
            </h4>
            @php
                $audiences = [
                    'tenant_admins' => ['icon'=>'fa-building','label'=>'Administradores de Clientes','desc'=>'1 usuário admin por organização cadastrada'],
                    'all_users'     => ['icon'=>'fa-users','label'=>'Todos os Usuários Ativos','desc'=>'Todos os usuários com status ativo no sistema'],
                    'leads'         => ['icon'=>'fa-user-plus','label'=>'Leads (Landing Pages)','desc'=>'Contatos capturados pelas páginas de captura'],
                    'all'           => ['icon'=>'fa-globe','label'=>'Todos (Usuários + Leads)','desc'=>'Combinação completa sem duplicatas'],
                    'none'          => ['icon'=>'fa-at','label'=>'Somente E-mails Avulsos','desc'=>'Apenas os e-mails inseridos manualmente abaixo'],
                ];
            @endphp
            @foreach($audiences as $val => $aud)
            <label style="display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:12px; border:2px solid {{ old('audience_type') == $val ? '#6366f1' : '#f1f5f9' }}; margin-bottom:10px; cursor:pointer; transition:border-color 0.2s;"
                   onclick="selectAudience(this, '{{ $val }}')">
                <input type="radio" name="audience_type" value="{{ $val }}" {{ old('audience_type') == $val ? 'checked' : ($val == 'tenant_admins' && !old('audience_type') ? 'checked' : '') }}
                       style="margin-top:3px; accent-color:#6366f1;">
                <div>
                    <div style="font-weight:800; color:#1e293b; font-size:0.88rem; margin-bottom:3px;">
                        <i class="fas {{ $aud['icon'] }} me-1" style="color:#6366f1;"></i>{{ $aud['label'] }}
                    </div>
                    <div style="color:#64748b; font-size:0.75rem;">{{ $aud['desc'] }}</div>
                </div>
            </label>
            @endforeach
        </div>

        {{-- Info box --}}
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:16px; padding:20px; margin-bottom:20px;">
            <div style="font-weight:800; color:#1d4ed8; font-size:0.85rem; margin-bottom:10px;">
                <i class="fas fa-circle-info me-2"></i>Como funciona
            </div>
            <ol style="color:#1e40af; font-size:0.78rem; line-height:1.8; margin:0; padding-left:16px;">
                <li>Crie e salve o rascunho</li>
                <li>Revise o preview do e-mail</li>
                <li>Clique em <strong>Disparar</strong> na lista</li>
                <li>O Brevo processa e envia</li>
                <li>Atualize as métricas após o envio</li>
            </ol>
        </div>

        {{-- E-mails manuais --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:20px;">
            <h4 style="margin:0 0 6px; font-weight:900; color:#1e293b; font-size:1.05rem;">
                <i class="fas fa-at me-2" style="color:#6366f1;"></i>E-mails avulsos
            </h4>
            <p style="color:#64748b; font-size:0.78rem; margin:0 0 14px; line-height:1.6;">
                Adicione endereços extras além do público selecionado. Um por linha, vírgula ou ponto-e-vírgula.<br>
                Aceita: <code style="font-size:0.72rem; background:#f1f5f9; padding:2px 5px; border-radius:4px;">email@exemplo.com</code>
                ou <code style="font-size:0.72rem; background:#f1f5f9; padding:2px 5px; border-radius:4px;">Nome &lt;email@exemplo.com&gt;</code>
            </p>
            <textarea name="manual_emails_raw" id="manualEmails" rows="6"
                      placeholder="joao@empresa.com&#10;Maria Silva <maria@empresa.com>&#10;ana@gmail.com"
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
            <a href="{{ route('admin.email_campaigns.index') }}"
               style="display:block; text-align:center; padding:12px; border:2px solid #e2e8f0; border-radius:12px; color:#64748b; font-weight:700; font-size:0.88rem; text-decoration:none;">
                Cancelar
            </a>
        </div>
    </div>

</div>
</form>

<script>
function togglePreview() {
    const pane  = document.getElementById('previewPane');
    const frame = document.getElementById('previewFrame');
    if (pane.style.display === 'none') {
        pane.style.display = 'block';
        const html = document.getElementById('htmlContent').value;
        frame.srcdoc = html || '<p style="font-family:sans-serif;color:#94a3b8;padding:40px;text-align:center;">Nenhum conteúdo ainda.</p>';
    } else {
        pane.style.display = 'none';
    }
}

function selectAudience(label, val) {
    document.querySelectorAll('[onclick^="selectAudience"]').forEach(l => l.style.borderColor = '#f1f5f9');
    label.style.borderColor = '#6366f1';
}

function countEmails(textarea) {
    const lines = textarea.value.split(/[\n,;]+/).map(l => l.trim()).filter(l => l.length > 0);
    const valid = lines.filter(l => {
        const m = l.match(/<([^>]+)>/);
        const email = m ? m[1] : l;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    });
    const el = document.getElementById('emailCount');
    if (el) el.textContent = valid.length > 0 ? valid.length + ' e-mail(s) válido(s) detectado(s)' : '';
}

function insertTemplate() {
    const tpl = `<!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vivensi</title></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
  <tr><td style="background:linear-gradient(135deg,#4f46e5,#3730a3);padding:36px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:26px;font-weight:800;">Vivensi</h1>
  </td></tr>
  <tr><td style="padding:40px;">
    <h2 style="color:#0f172a;margin:0 0 16px;font-size:22px;font-weight:700;">Olá! 👋</h2>
    <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 20px;">
      <!-- Escreva o conteúdo principal aqui -->
      Insira aqui o corpo do seu e-mail com novidades, informações ou comunicados importantes.
    </p>
    <div style="text-align:center;margin:30px 0;">
      <a href="https://vivensi.app.br" style="background:#4f46e5;color:#fff;padding:14px 28px;text-decoration:none;border-radius:8px;font-weight:700;display:inline-block;">
        Acessar a Plataforma
      </a>
    </div>
    <p style="color:#64748b;font-size:14px;line-height:1.7;margin:0;">
      Atenciosamente,<br><strong>Equipe Vivensi</strong>
    </p>
  </td></tr>
  <tr><td style="background:#f1f5f9;padding:24px;text-align:center;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;">
    <p style="margin:0 0 8px;">&copy; {{ date('Y') }} Vivensi. Todos os direitos reservados.</p>
    <p style="margin:0;">Você está recebendo este e-mail pois é cliente ou lead da Vivensi.</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>`;
    document.getElementById('htmlContent').value = tpl;
}

// ── Upload de imagem — insere <img> no cursor do textarea ────────────────
document.getElementById('emailImgUpload').addEventListener('change', async function (e) {
    var file = e.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('Imagem maior que 5MB. Reduza antes de subir.');
        e.target.value = '';
        return;
    }

    var textarea = document.getElementById('htmlContent');
    var originalPlaceholder = textarea.placeholder;
    textarea.placeholder = 'Enviando imagem...';

    var form = new FormData();
    form.append('image', file);

    try {
        var r = await fetch('{{ route("admin.email_campaigns.upload_image") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: form,
        });
        var data = await r.json();
        if (!r.ok || !data.url) {
            alert('Falha ao enviar: ' + (data.error || 'erro desconhecido'));
            return;
        }

        // Insere <img> no cursor (se cursor perdido, no fim do texto)
        var imgTag = '<img src="' + data.url + '" alt="" style="max-width:100%; height:auto; display:block;">';
        var start = textarea.selectionStart;
        var end   = textarea.selectionEnd;
        var before = textarea.value.substring(0, start);
        var after  = textarea.value.substring(end);
        textarea.value = before + imgTag + after;
        // move cursor pra depois do <img>
        var newPos = start + imgTag.length;
        textarea.setSelectionRange(newPos, newPos);
        textarea.focus();
    } catch (err) {
        alert('Falha de rede ao enviar imagem.');
    } finally {
        textarea.placeholder = originalPlaceholder;
        e.target.value = ''; // permite subir a mesma imagem de novo se quiser
    }
});

// ── Bruce IA F1: analise pre-envio ────────────────────────────────────────
document.getElementById('btnBruceAnalyze').addEventListener('click', async function () {
    var btn = this;
    var box = document.getElementById('bruceAnalyzeResult');
    var subject = document.querySelector('input[name="subject"]').value.trim();
    var html    = document.getElementById('htmlContent').value.trim();

    if (!subject || !html) {
        box.style.display = 'block';
        box.innerHTML = '<div style="padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px;">Preencha assunto e corpo antes de analisar.</div>';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Analisando...';
    box.style.display = 'block';
    box.innerHTML = '<div style="padding:12px; background:#eff6ff; color:#1e40af; border-radius:8px;">🤖 Bruce esta analisando...</div>';

    try {
        var r = await fetch('{{ route("admin.email_campaigns.ai.analyze") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ subject: subject, html_content: html }),
        });
        var data = await r.json();

        if (!r.ok || data.error) {
            box.innerHTML = '<div style="padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px;">Erro: ' + (data.error || 'falha') + '</div>';
            return;
        }

        var scoreColor = data.score >= 85 ? '#16a34a' : (data.score >= 70 ? '#3b82f6' : (data.score >= 50 ? '#f59e0b' : '#dc2626'));
        var levelLabel = { excelente: 'Excelente', bom: 'Bom', atencao: 'Atencao', ruim: 'Ruim' }[data.level] || data.level;

        var html_out = '<div style="padding:16px; background:#fff; border-radius:10px;">';
        html_out += '<div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">';
        html_out += '<div style="width:70px; height:70px; border-radius:50%; background:' + scoreColor + '; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:800;">' + data.score + '</div>';
        html_out += '<div><div style="font-weight:700; color:#0f172a;">' + levelLabel + '</div><div style="font-size:.85rem; color:#64748b;">Score anti-spam / entregabilidade</div></div>';
        html_out += '</div>';

        if (data.risks && data.risks.length) {
            html_out += '<div style="margin-bottom:12px;"><strong style="font-size:.9rem; color:#0f172a;">Riscos identificados</strong>';
            data.risks.forEach(function (risk) {
                var sevColor = risk.severity === 'high' ? '#dc2626' : (risk.severity === 'medium' ? '#f59e0b' : '#64748b');
                html_out += '<div style="padding:8px 12px; margin-top:6px; background:#f8fafc; border-left:3px solid ' + sevColor + '; border-radius:4px; font-size:.85rem; color:#334155;">' + risk.msg + '</div>';
            });
            html_out += '</div>';
        } else {
            html_out += '<div style="padding:8px 12px; background:#dcfce7; color:#166534; border-radius:6px; font-size:.85rem; margin-bottom:12px;">Nenhum risco identificado.</div>';
        }

        if (data.suggestions && data.suggestions.length) {
            html_out += '<div style="margin-bottom:8px;"><strong style="font-size:.9rem; color:#0f172a;">Sugestoes</strong><ul style="margin:6px 0 0 20px; font-size:.85rem; color:#334155;">';
            data.suggestions.forEach(function (s) { html_out += '<li style="margin:3px 0;">' + s + '</li>'; });
            html_out += '</ul></div>';
        }

        if (data.ai_notes) {
            html_out += '<div style="padding:10px 12px; background:#eff6ff; border-radius:6px; font-size:.82rem; color:#1e40af; font-style:italic;">🤖 ' + data.ai_notes + '</div>';
        }

        html_out += '</div>';
        box.innerHTML = html_out;
    } catch (e) {
        box.innerHTML = '<div style="padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px;">Falha de rede.</div>';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Analisar de novo';
    }
});
</script>
@endsection
