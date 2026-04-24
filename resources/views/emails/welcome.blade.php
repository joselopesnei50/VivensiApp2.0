@extends('emails.layout')

@section('title', 'Bem-vindo ao Vivensi')

@section('content')

{{-- Saudação --}}
<h1 style="text-align:center;font-size:26px;font-weight:900;color:#0f172a;letter-spacing:-0.5px;margin-bottom:6px;">
    Olá, {{ explode(' ', $user->name)[0] }}! 🎉
</h1>
<p style="text-align:center;color:#64748b;font-size:15px;margin-top:0;">
    Sua conta na <strong style="color:#4f46e5;">Vivensi</strong> está ativa.<br>
    Bem-vindo à plataforma que transforma a gestão da sua organização.
</p>

{{-- Card resumo --}}
<div style="background:#f8faff;border:1px solid #e0e7ff;border-radius:14px;padding:20px 24px;margin:28px 0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#64748b;">Organização</td>
            <td style="padding:4px 0;font-size:13px;font-weight:700;color:#0f172a;text-align:right;">{{ $user->tenant->name ?? 'Não informada' }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#64748b;">Plano</td>
            <td style="padding:4px 0;font-size:13px;font-weight:700;color:#4f46e5;text-align:right;">{{ $planName }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#64748b;">E-mail</td>
            <td style="padding:4px 0;font-size:13px;font-weight:700;color:#0f172a;text-align:right;">{{ $user->email }}</td>
        </tr>
    </table>
</div>

{{-- 3 primeiros passos --}}
<p style="font-size:15px;font-weight:800;color:#0f172a;margin-bottom:16px;">Por onde começar:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
    <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="36">
                        <div style="width:32px;height:32px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#7c3aed;text-align:center;line-height:32px;">1</div>
                    </td>
                    <td style="padding-left:12px;">
                        <div style="font-size:13px;font-weight:700;color:#0f172a;">Complete seu perfil</div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Adicione logo, telefone e personalize sua conta.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="36">
                        <div style="width:32px;height:32px;background:#dcfce7;border-radius:8px;font-size:14px;font-weight:900;color:#16a34a;text-align:center;line-height:32px;">2</div>
                    </td>
                    <td style="padding-left:12px;">
                        <div style="font-size:13px;font-weight:700;color:#0f172a;">
                            @if(($user->role ?? '') === 'manager') Crie seu primeiro projeto
                            @elseif(($user->role ?? '') === 'ngo') Cadastre seu primeiro doador
                            @else Crie sua primeira tarefa @endif
                        </div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Coloque o sistema em movimento com dados reais.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:10px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="36">
                        <div style="width:32px;height:32px;background:#fef3c7;border-radius:8px;font-size:14px;font-weight:900;color:#d97706;text-align:center;line-height:32px;">3</div>
                    </td>
                    <td style="padding-left:12px;">
                        <div style="font-size:13px;font-weight:700;color:#0f172a;">Explore o Bruce AI</div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Seu assistente de IA está pronto para análises, prospecção e geração de conteúdo.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- CTA --}}
<center>
    @component('emails.components.button', ['url' => config('app.url') . '/dashboard'])
        Acessar meu Painel agora →
    @endcomponent
</center>

{{-- Suporte --}}
<p style="text-align:center;font-size:13px;color:#94a3b8;margin-top:32px;margin-bottom:0;">
    Dúvidas? Nossa equipe está disponível pelo WhatsApp e e-mail.<br>
    <a href="mailto:suporte@vivensi.app.br" style="color:#4f46e5;text-decoration:none;">suporte@vivensi.app.br</a>
</p>

@endsection
