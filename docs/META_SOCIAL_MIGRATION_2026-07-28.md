# Configuração do sistema de posts sociais no NC5HUBDIGITAL-EMP

**Data:** 2026-07-28
**Contexto:** o Vivensi **nunca teve um app Meta real em produção** — a memória
antiga citava um NCHUBDIGITAL, mas ele nunca chegou a ser usado (sem clientes
conectados). O NC5HUBDIGITAL-EMP (App ID `1421767266445706`, Tech Provider
verificado em 2026-07-28) é o **primeiro e único** app Meta do sistema.

Este documento cobre a primeira configuração do sistema de posts sociais
(FB Pages + Instagram Business) usando esse app, aproveitando o mesmo esforço
de app review pra submeter em um único pacote as 6 permissões necessárias
(Pages + Instagram + `business_management`).

## Identificação oficial do app

| Campo | Valor |
|---|---|
| Nome | **NC5HUBDIGITAL-EMP** |
| App ID | `1421767266445706` |
| Empresa (Business Portfolio) | Vivensi-NC5-BRUCEAI |
| Modo | **Em desenvolvimento** *(precisa mudar pra Live mode antes de operar clientes reais)* |
| Verificação de negócio | ✅ Aprovada |
| Verificação de Tech Provider | ✅ Aprovada em 2026-07-28 |
| Permissões WhatsApp aprovadas | `whatsapp_business_messaging`, `whatsapp_business_management` |
| Permissões pendentes de reenvio | `business_management` (rejeitada) + 5 novas (Pages + IG) |

---

## Situação atual

Sistema de posts (`SocialAccount`, `ScheduledPost`, `AiSocialPost`) tem código
pronto mas **nunca foi ativado em produção pra clientes**. Escopos pedidos hoje
no OAuth (`MetaSocialAuthService::getAuthUrl`): `pages_show_list`,
`pages_read_engagement`, `pages_manage_posts` — nenhum aprovado.

O código do publisher (`MetaSocialPublisherService`) usa Instagram Graph API,
mas o `MetaSocialAuthService` **não pede** os escopos `instagram_basic` nem
`instagram_content_publish` — bug latente: mesmo se aprovar as permissões de
Pages, IG continuaria falhando. Já corrigido no commit `e7c4bfe`.

## Alvo

Ativar tudo em **NC5HUBDIGITAL-EMP** (App ID `1421767266445706`):

- **Tech Provider verificado** → aprovações Meta rodam mais rápido
- Um único app pra WhatsApp Cloud + FB Pages + Instagram Business
- Um único app review pra reenviar `business_management` + submeter Pages + IG

## Configuração de **credenciais**, não migração de código

Toda a integração já lê `app_id` e `app_secret` de `SystemSetting`. Como não
há dados de clientes reais em `social_accounts`, é primeira configuração
limpa. O código estrutural já está no lugar. As únicas alterações de código
que já foram feitas (commit `e7c4bfe`):

1. 3 escopos novos no `MetaSocialAuthService::getAuthUrl()`
2. Bump da versão da Graph API v20 → v22

---

## Passo a passo

### 1. No painel Meta do app **NC5HUBDIGITAL-EMP** (App ID `1421767266445706`)

**a. Adicionar produto "Facebook Login para Empresas"** (se ainda não tem)
- App → Adicionar produto → Facebook Login for Business
- Já usa esse produto pro Embedded Signup do WhatsApp — só reaproveita

**b. Registrar as 3 URIs (idênticas ao app antigo)**
- Valid OAuth Redirect URIs: `https://vivensi.app.br/social/facebook/callback`
- Deauthorize Callback URL: `https://vivensi.app.br/social/facebook/deauthorize`
- Data Deletion Request URL: `https://vivensi.app.br/social/facebook/data-deletion`

**c. Confirmar produto "Instagram Graph API"** habilitado
- Sem isso, os escopos `instagram_*` não aparecem no OAuth

**d. Copiar App ID e App Secret novos** — usa no passo 3

### 2. Ajustes de código (pequenos, num único commit)

**Arquivo:** `app/Services/MetaSocialAuthService.php`

Trocar o array de escopos no `getAuthUrl()` (linha ~32) para incluir Instagram
e `business_management`:

```php
$scopes = implode(',', [
    'public_profile',
    // Facebook Pages
    'pages_show_list',
    'pages_read_engagement',
    'pages_manage_posts',
    // Instagram Business
    'instagram_basic',
    'instagram_content_publish',
    // Assets management (obrigatório pra ler os Business assets do cliente)
    'business_management',
]);
```

Opcional mas recomendado, no mesmo commit:

```php
private string $graphVersion = 'v22.0'; // era v20.0
```

E no `MetaSocialPublisherService.php`, mesma linha:
```php
private string $graphVersion = 'v22.0';
```

Zero mudança em rota, controller, view, migration ou model.

### 3. Trocar credenciais em produção

**Opção A — pelo painel `/admin/settings` (recomendada)**

- Menu Admin → Settings → seção "Meta Social"
- Trocar `meta_social_app_id` para `1421767266445706`
- Trocar `meta_social_app_secret` para o novo App Secret
- Salvar

**Opção B — via tinker no VPS (fallback se painel indisponível)**

```bash
cd /var/www/vivensi && php artisan tinker --execute="
\App\Models\SystemSetting::setValue('meta_social_app_id', '1421767266445706', 'social');
\App\Models\SystemSetting::setValue('meta_social_app_secret', 'COLA_O_SECRET_AQUI', 'social');
echo 'OK';
"
```

### 4. Higienizar contas conectadas antigas — NÃO SE APLICA

Como o Vivensi nunca teve app Meta ativo em produção, `social_accounts` está
vazia (ou só com registros de teste do próprio dev). Nada pra higienizar,
nenhum cliente pra comunicar. Passo pulado.

Se por acaso houver algum registro de teste isolado, roda pra limpar:

```bash
cd /var/www/vivensi && php artisan tinker --execute="
echo 'Contas antes: ' . \App\Models\SocialAccount::withoutGlobalScopes()->count() . PHP_EOL;
"
```

Se retornar `0`, nada a fazer. Se retornar > 0, avalia caso a caso.

### 5. Testar internamente antes do App Review

- Adicionar teu usuário como **Test User** no painel do NC5HUBDIGITAL-EMP
  (App → Roles → Test Users → Add)
- Fazer o fluxo `/social/accounts` → conectar Facebook → autorizar todos os
  escopos → confirmar que aparece a página e IG Business associado
- Criar um post agendado, forçar publicação, ver se sai
- Testar tanto FB Page quanto IG Business

Se falhar, o log em `storage/logs/laravel.log` linha "Meta OAuth token exchange
failed" indica app_id/secret errado. "Meta get pages failed" indica escopo
faltando ou usuário não aceitou permissão.

### 6. Reversão (rollback)

Se algo der errado antes do App Review, reverter é trivial:

```bash
cd /var/www/vivensi && php artisan tinker --execute="
\App\Models\SystemSetting::setValue('meta_social_app_id', '1046888724675814', 'social');
\App\Models\SystemSetting::setValue('meta_social_app_secret', 'SECRET_ANTIGO', 'social');
echo 'OK';
"
```

E `git revert` do commit do passo 2.

---

## Ordem cronológica sugerida

| # | Ação | Quem | Duração | Bloqueia |
|---|------|------|---------|----------|
| 1 | Ativar Live mode do WhatsApp no NC5HUBDIGITAL-EMP | Você (Meta panel) | 5 min | — |
| 2 | Adicionar Facebook Login for Business + IG Graph API no NC5HUBDIGITAL-EMP | Você (Meta panel) | 15 min | — |
| 3 | Cadastrar URIs de callback / deauthorize / data-deletion | Você (Meta panel) | 5 min | 2 |
| 4 | Ajuste de escopos no `MetaSocialAuthService` + commit | Eu | 10 min | — |
| 5 | Trocar SystemSettings no VPS | Você (`/admin/settings`) | 3 min | 3, 4 |
| 6 | Desativar tokens antigos + banner de reconexão | Você (tinker) | 2 min | 5 |
| 7 | Adicionar-se como Test User + testar interno | Você | 30 min | 5, 6 |
| 8 | Gravar vídeo do fluxo completo (PT com legenda EN) | Você | 60 min | 7 |
| 9 | Reenviar App Review das 6 permissões | Você (Meta panel) | 30 min | 8 |
| 10 | Aguardar 3-15 dias úteis | Meta | — | — |

## Após aprovação

- App já está Live (feito no passo 1)
- Ativar tudo em produção sem mais mudança de código
- Comunicar clientes que o feature "Publicar em FB + IG" ficou disponível
