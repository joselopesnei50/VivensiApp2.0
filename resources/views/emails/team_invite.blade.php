<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f3f7;font-family:Helvetica,Arial,sans-serif;color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f3f7;">
<tr><td align="center" style="padding:28px 12px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;">
    <tr><td style="background:#0b0d14;padding:32px 36px 28px;">
      <img src="{{ config('app.url') }}/img/novalogo.png" alt="Vivensi" width="110" style="display:block;width:110px;height:auto;border:0;">
      <div style="height:22px;line-height:22px;">&nbsp;</div>
      <div style="font-size:12px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#7d93ff;">Convite para a equipe</div>
      <div style="font-size:24px;line-height:30px;font-weight:bold;color:#ffffff;margin-top:8px;">{{ $orgName }} quer você na equipe</div>
    </td></tr>
    <tr><td style="height:3px;line-height:3px;font-size:0;background:#4F6EF7;">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 6px;font-size:16px;line-height:26px;">
      <p style="margin:0 0 14px;">Olá, {{ explode(' ', trim($member->name))[0] }}!</p>
      <p style="margin:0 0 14px;"><strong>{{ $invitedBy->name }}</strong> cadastrou você {{ $projectName ? 'no projeto ' . $projectName . ' da' : 'na equipe da' }} <strong>{{ $orgName }}</strong> na Vivensi, a plataforma de gestão da organização.</p>
    </td></tr>
    <tr><td style="padding:4px 36px 8px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
        <tr><td style="padding:16px 20px;font-size:15px;line-height:22px;">
          @if($projectName)<span style="color:#64748b;">Projeto:</span> <strong style="color:#0f172a;">{{ $projectName }}</strong><br>@endif
          <span style="color:#64748b;">Sua função:</span> <strong style="color:#0f172a;">{{ $roleLabel }}</strong><br>
          <span style="color:#64748b;font-size:14px;">{{ $roleDescription }}</span>
        </td></tr>
      </table>
    </td></tr>
    <tr><td align="center" style="padding:26px 36px 10px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
        <td align="center" style="border-radius:10px;background:#0f0f0f;">
          <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:15px 34px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;">Criar minha senha &rarr;</a>
        </td>
      </tr></table>
    </td></tr>
    <tr><td style="padding:12px 36px 30px;font-size:14px;line-height:22px;color:#64748b;">
      O link vale por <strong>72 horas</strong>. Depois de criar a senha, entre com o e-mail <strong>{{ $member->email }}</strong> em
      <a href="{{ config('app.url') }}/login" style="color:#4F6EF7;">vivensi.app.br/login</a>.<br><br>
      Não esperava este convite? Pode ignorar este e-mail.
    </td></tr>
    <tr><td style="background:#f8fafc;border-top:1px solid #eef1f5;padding:16px 36px;text-align:center;font-size:12px;color:#94a3b8;">
      Vivensi: gestão para o Terceiro Setor
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
