@extends('emails.layout')

@section('title', 'Seu Portal VIP de Doador')

@section('content')
<h1 style="color:#0f172a;font-size:22px;font-weight:800;margin-top:0;margin-bottom:8px;">
    Olá, {{ explode(' ', $donor->name)[0] }}! 💙
</h1>

<p style="color:#475569;font-size:15px;margin-bottom:28px;">
    A <strong>{{ $tenant->name ?? 'nossa organização' }}</strong> preparou um Portal VIP exclusivo para você. Nele você pode:
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td style="padding:12px 16px;background:#f0fdf4;border-radius:10px;border-left:4px solid #10b981;margin-bottom:10px;display:block;">
            <span style="font-size:20px;margin-right:10px;">📊</span>
            <strong style="color:#065f46;">Acompanhar o histórico e impacto das suas doações</strong>
        </td>
    </tr>
    <tr><td style="height:10px;"></td></tr>
    <tr>
        <td style="padding:12px 16px;background:#eff6ff;border-radius:10px;border-left:4px solid #3b82f6;">
            <span style="font-size:20px;margin-right:10px;">📄</span>
            <strong style="color:#1e40af;">Baixar seu Informe de Rendimentos para o IR</strong>
        </td>
    </tr>
    <tr><td style="height:10px;"></td></tr>
    <tr>
        <td style="padding:12px 16px;background:#faf5ff;border-radius:10px;border-left:4px solid #8b5cf6;">
            <span style="font-size:20px;margin-right:10px;">✏️</span>
            <strong style="color:#5b21b6;">Atualizar seus dados de contato</strong>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="{{ $portalUrl }}"
               style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#ffffff;font-weight:700;font-size:16px;padding:16px 40px;border-radius:12px;text-decoration:none;letter-spacing:0.3px;">
                ✨ Acessar Meu Portal VIP
            </a>
        </td>
    </tr>
</table>

<p style="color:#64748b;font-size:13px;text-align:center;margin-bottom:8px;">
    Ou copie e cole este link no seu navegador:
</p>
<p style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px;color:#475569;word-break:break-all;text-align:center;margin-bottom:28px;">
    {{ $portalUrl }}
</p>

<hr style="border:none;border-top:1px solid #f1f5f9;margin:0 0 24px;">

<p style="color:#94a3b8;font-size:12px;text-align:center;margin:0;">
    Este link é exclusivo e intransferível. Não compartilhe com outras pessoas.<br>
    Enviado por <strong>{{ $tenant->name ?? 'Vivensi' }}</strong> via plataforma Vivensi.
</p>
@endsection
