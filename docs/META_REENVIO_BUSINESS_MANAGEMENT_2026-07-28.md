# Reenvio de `business_management` — texto alinhado ao vídeo existente

**Data:** 2026-07-28
**Ação:** reenviar APENAS a permissão `business_management` no app
NC5HUBDIGITAL-EMP (App ID `1421767266445706`).

## Diagnóstico da rejeição anterior

**Vídeo enviado**: mostrava `/whatsapp/cloud/templates` — sincronização e
envio de template server-side.

**Texto enviado**: descrevia o fluxo Embedded Signup completo (FB.login,
authorization code, token exchange, etc).

**Resultado**: Meta viu texto sobre fluxo A e vídeo sobre fluxo B →
"screencast não alinhado com detalhes do caso de uso".

**Não é problema de**: áudio (outros 2 vídeos aprovados também eram
mudos), arquitetura, ou funcionalidade. É problema de **desalinhamento
entre texto e vídeo**.

## Fix

Reenviar reutilizando o **mesmo vídeo** (envio de template). Apenas
substituir o texto por uma descrição que fale **do que o vídeo mostra**,
não do fluxo Embedded Signup que não aparece nele.

## Texto novo (copiar e colar no formulário)

### Campo: "Conte para nós como você está usando essa permissão ou recurso"

```
Our app (NC5HUBDIGITAL-EMP / Vivensi) is a server-to-server SaaS
platform. We use business_management exclusively at the backend to
manage WhatsApp Business Assets (WhatsApp Business Accounts and phone
numbers) belonging to our clients, using System User Access Tokens that
each client generates and provides to us through our onboarding
interface.

Specifically, business_management is required so that our backend can:

1. Read the WhatsApp Business Account (WABA) metadata for each client
   who onboards, calling GET /{waba_id} to validate the credentials
   the client provided.
2. Subscribe our app to receive webhooks from the client's WABA,
   calling POST /{waba_id}/subscribed_apps.
3. Read and manage message templates that belong to the client's
   WABA, which is demonstrated in the attached video: the platform
   lists all templates of the client's WABA (GET /{waba_id}/message_templates),
   allows creating and sending them (POST /{phone_number_id}/messages
   with template payload).

We do NOT use business_management to:
- Access ad accounts, catalogs, pages, or Instagram accounts.
- Collect analytics or marketing data.
- Make aggregated or anonymous data requests.
- Read business assets outside of WhatsApp Business.

The attached video demonstrates the concrete result of this permission
in action: our platform interface (/whatsapp/cloud/templates) reading
the message templates from a WhatsApp Business Account and sending
one to a real WhatsApp number. Every API call to Meta shown in the
video is executed server-side from our infrastructure at vivensi.app.br
using a System User Access Token — there is no user-facing Meta API
interaction after login.

Data handling: WhatsApp Business Account IDs, phone number IDs and
System User Access Tokens are stored encrypted at rest (AES-256) in
our database, isolated per tenant. Tokens are never logged or displayed.
```

### Campo: "Provide a detailed step-by-step video walkthrough..."

**Reutilizar o mesmo link/upload de vídeo do envio anterior.** Não
precisa gravar novo. O vídeo mostra exatamente o que o texto descreve:
templates de uma WABA sendo listados e enviados via API server-side.

### Campo checkbox de concordância

`I agree that any data I receive through business_management will be
used in accordance with the allowed usage.` → **marcar**.

## Web reviewer instructions (reaproveitar do envio anterior)

Se pedir de novo, usar o mesmo texto do último envio — credenciais de
teste continuam válidas:

```
URL: https://vivensi.app.br/login
Email: metareview@vivensi.app.br
Password: password

Test the business_management usage:
1. Log in with the credentials above
2. Navigate to /whatsapp/cloud/templates
3. Click "Sincronizar" — this calls GET /{waba_id}/message_templates
   server-side (uses business_management)
4. Click "Enviar teste" on any approved template
5. The message is delivered via WhatsApp

Test WABA: 1331534925713250 / Phone Number ID: 1168655716337509
Test number: +1 (555) 167-1404
```

## Por que só business_management, e não Pages/Instagram junto

O sistema de posts sociais em `SocialAccountController` /
`MetaSocialPublisherService` existe no código mas **nunca operou em
produção com cliente real**. Submeter Pages/Instagram agora seria
mostrar tela vazia ao reviewer, o que a Meta rejeita.

Estratégia: primeiro **acertar `business_management`** (que já tem uso
comprovado em produção). Quando tiver 1-2 clientes reais publicando em
Pages/Instagram via Vivensi, submete Pages+Instagram num reenvio
dedicado com prints reais.

## Checklist final antes de clicar "Enviar para análise"

- [ ] App em modo Em tempo real (Live) — confirmado
- [ ] Texto novo colado no campo `business_management`
- [ ] Vídeo do envio anterior reaproveitado (não regravado)
- [ ] Checkbox de concordância marcado
- [ ] Instruções pro reviewer alinhadas com o vídeo (templates)
- [ ] **Pages e Instagram permissions NÃO adicionadas** desta vez
- [ ] Apenas `business_management` no envio
