# Métricas de Redes Sociais (Facebook + Instagram)

**Data:** 2026-07-30
**Status:** ⏸️ **CONGELADO — aguardando App Review Meta**. Código pronto, scheduler desligado.
**Escopo:** captura automática de impressões, alcance, curtidas, comentários, compartilhamentos, saves, cliques e engajamento total de posts publicados via Meta Graph API v22

## ⚠️ Estado atual (2026-07-30 fim do dia)

Após teste de fogo real no VPS com post publicado corretamente (fluxo 2 fases funcionou, id `PAGEID_POSTID` salvo com underscore), **Meta v22 continuou rejeitando Insights com 400** (`"The value must be a valid insights metric"`). Diagnóstico definitivo: mesmo `post_impressions` básico agora exige permissões que não temos.

**Decisão:** modo "aguardando App Review":
1. Scheduler `posts:sync-metrics` **comentado em `Kernel.php`** — não gera mais warning hourly
2. Banner de aviso adicionado em `/social/analytics` explicando ao usuário
3. Todo o resto do código continua funcional

**Pra religar quando permissões forem aprovadas:** descomentar as 5 linhas em `app/Console/Kernel.php` (procurar por "DESLIGADO em 2026-07-30").

**Permissões requeridas pra destravar:**
- `pages_read_user_content` — comments/shares/reactions do FB
- `instagram_manage_insights` — tudo do IG
- `read_insights` — impressions/clicks do FB (Meta v22 endureceu esse endpoint)

---

## 1. Objetivo

Coletar métricas de performance dos posts publicados nas redes sociais dos tenants (Facebook Pages + Instagram Business), armazenar em `post_metrics` e expor num dashboard `/social/analytics` com filtros de período (7/30/90 dias), top 5 posts por engajamento e comparativo por rede.

Rodagem automática: hourly via scheduler (`posts:sync-metrics --days=30`).

---

## 2. Arquitetura entregue

### 2.1. Modelos

**`app/Models/PostMetric.php`** — estendido pra suportar dupla fonte (FB e IG no mesmo post):

```php
public const SOURCE_FACEBOOK  = 'facebook';
public const SOURCE_INSTAGRAM = 'instagram';

protected $fillable = [
    'scheduled_post_id', 'tenant_id', 'source',
    'likes', 'comments', 'shares', 'reach', 'impressions',
    'clicks', 'saved', 'engagement', 'fetched_at',
];
```

Cada post publicado em **ambas** as redes gera 2 linhas em `post_metrics` (`source=facebook` + `source=instagram`).

**`app/Models/ScheduledPost.php`** — trocado de `hasOne` pra `hasMany`:

```php
public function metrics()  { return $this->hasMany(PostMetric::class, 'scheduled_post_id'); }
public function metric()   { return $this->hasOne(PostMetric::class, 'scheduled_post_id')->latestOfMany('fetched_at'); }
public function metricsTotal(): array { /* soma FB + IG */ }
```

### 2.2. Service de coleta

**`app/Services/MetaSocialInsightsService.php`** — NOVO. Roteia por `facebook_post_id` e `instagram_post_id` e sincroniza métricas em endpoints separados:

- `syncFacebook()` — usa **Insights API** (`/{post_id}/insights` para impressões/likes/clicks) **+ Engagement summary** (`/{post_id}?fields=comments.summary(true),shares,reactions.summary(true)` para totais de comentários/shares/likes). Dois endpoints porque insights não retorna comments/shares.
- `syncInstagram()` — usa **IG Insights** (`/{ig_media_id}/insights` com métricas `impressions,reach,likes,comments,saved,shares`). Fallback pra `impressions,reach` mínimos quando falta permissão.

Ambos gravam via `PostMetric::withoutGlobalScope('tenant')->updateOrCreate` respeitando `tenant_id` do post original.

Falhas são **não-bloqueantes**: token expirado, permissão faltando, métrica descontinuada — todos logam `warning` e seguem.

### 2.3. Command

**`app/Console/Commands/SyncSocialMetrics.php`** — NOVO. `posts:sync-metrics --days=N`

```php
$posts = ScheduledPost::withoutGlobalScopes()->with('account')
    ->where('status', 'published')
    ->where(function ($q) {
        $q->whereNotNull('facebook_post_id')->orWhereNotNull('instagram_post_id');
    })
    ->where('scheduled_at', '>=', now()->subDays($days))->get();
```

Percorre e chama `MetaSocialInsightsService::syncPost()`. Loga resumo `X OK, Y falhas, Z processados`.

### 2.4. Scheduler

**`app/Console/Kernel.php`** — adicionado:

```php
$schedule->command('posts:sync-metrics --days=30')->hourly()
    ->onFailure(function () {
        Log::error('posts:sync-metrics falhou no scheduler.');
    });
```

### 2.5. Dashboard

**`app/Http/Controllers/SocialAnalyticsController.php`** — NOVO. Rota `/social/analytics?days=7|30|90`.

Retorna:
- `$totals` — soma dos 30 dias (impressões, alcance, likes, comments, shares, posts)
- `$byNetwork` — breakdown por rede (FB vs IG)
- `$topPosts` — top 5 posts por engajamento no período

**`resources/views/social/analytics.blade.php`** — NOVO. Layout com:
- Hero + filtro de período
- 6 cards KPI (1 destaque verde para "Impressões", 5 secundários)
- 2 cards de rede lado a lado com impressões/alcance/engajamento
- Lista dos top 5 com legenda truncada, timestamp, conta e métricas inline
- Empty state pra quando não tem post ou métricas ainda estão sendo coletadas

Links inseridos nos 3 blocos do menu do `layouts/app.blade.php` (super_admin, manager, ngo/common).

---

## 3. Problemas encontrados e como resolvemos

### 3.1. Métricas descontinuadas na Meta v22

**Sintoma:** `post_impressions_unique` e `post_engaged_users` retornam 400 "Invalid metric".

**Causa:** Meta deprecou desde v10+.

**Fix:** removidas do payload. Só chamamos `post_impressions`, `post_reactions_like_total`, `post_clicks`. Fallback pra `post_impressions` sozinho quando conta é nova.

### 3.2. Field `post_id` não existe mais no GET do objeto

**Sintoma:** `GET /{objectId}?fields=post_id` retorna 400 "nonexisting field post_id".

**Causa:** Meta v22 removeu esse field. Antes era possível pegar o `photo_id` e chamar `GET /photo_id?fields=post_id` pra descobrir o post real. Não funciona mais.

**Tentativa de fix (falha):** construir manualmente `{pageId}_{objectId}` — implementado no publisher e insights service (commit `db14834`).

**Por que não bastou:** o `objectId` retornado por `/photos published=true` **não é** o object_id do post no feed. É o media_fbid da foto no álbum. Se a foto **não virou post no feed** (bug abaixo), o id fica órfão.

### 3.3. **CAUSA RAIZ**: POST /photos published=true não publica no feed (Meta v22)

**Sintoma:** post #10 salvou `facebook_post_id = 913640478448972` (raw, sem underscore). Insights retorna 400.

**Prova via sondagem:**

```
GET /913640478448972                    → 400 "nonexisting field"
GET /107172892428351/photos             → não lista esse id
GET /107172892428351/posts              → posts REAIS têm id "PAGEID_POSTID"
                                          ex: 107172892428351_913640495115637
                                          (publicado 4min DEPOIS do post #10)
```

Conclusão: a foto foi criada no álbum mas **nunca virou post no feed**. Ficou órfã.

**Fix (commit `91fa66c`):** publicação em **2 fases** — padrão oficial Meta pra posts com foto:

```
Fase 1: POST /{pageId}/photos com published=FALSE
        → retorna media_fbid da foto no álbum

Fase 2: POST /{pageId}/feed com:
          message=<caption>
          attached_media=[{media_fbid: X}]
        → cria post REAL no feed com id PAGEID_POSTID
```

**Onde:** `MetaSocialPublisherService::publishFacebookPhotoPost()`. Vídeo continua via `/videos` (não tem esse bug). Texto puro continua via `/feed`.

### 3.4. IG Insights bloqueado por permissão

**Sintoma:** `GET /{ig_media_id}/insights` retorna 400 code 10 "Application does not have permission".

**Causa:** `instagram_manage_insights` não foi submetida no App Review (2/3 aprovadas até 2026-07-28; `business_management` foi rejeitada e vai ser reenviada em 26/09).

**Fix:** adicionar `instagram_manage_insights` no próximo App Review. Enquanto isso, IG metrics ficam zeradas e o service loga warning sem bloquear FB.

### 3.5. FB Engagement summary bloqueado por permissão

**Sintoma:** `GET /{post_id}?fields=comments.summary,reactions.summary` retorna 400 code 10 "requires 'pages_read_user_content' or 'Page Public Content Access'".

**Causa:** `pages_read_user_content` também não foi submetida.

**Fix:** adicionar no próximo App Review. Enquanto isso, `comments`, `shares` e `reactions.summary` ficam zeradas mas o insights básico (impressions/likes/clicks) funciona porque usa `pages_read_engagement` (aprovada).

### 3.6. Métrica `shares` no IG Insights

**Sintoma:** `GET /{ig_media_id}/insights?metric=impressions,reach,likes,comments,saved,shares` — Meta reclamou de field `shares` inexistente em contas novas.

**Fix:** fallback já implementado — se falhar com set completo, tenta só `impressions,reach`.

### 3.7. Post ainda não sincronizado por conta desativada

**Sintoma:** `syncPost()` chamado em post cuja `account.is_active = false`.

**Fix:** guard clause no início: `if (!$account || !$account->is_active || $post->status !== 'published') return;`.

---

## 4. Arquivos criados/modificados

| Arquivo | Ação |
|---|---|
| `app/Models/PostMetric.php` | estendido: source enum, novos campos, cast fetched_at |
| `app/Models/ScheduledPost.php` | hasMany metrics, metric()->latestOfMany, metricsTotal() |
| `app/Services/MetaSocialInsightsService.php` | **NOVO** |
| `app/Services/MetaSocialPublisherService.php` | publishToFacebook reescrito em 2 fases |
| `app/Console/Commands/SyncSocialMetrics.php` | **NOVO** |
| `app/Console/Kernel.php` | schedule hourly do sync-metrics |
| `app/Http/Controllers/SocialAnalyticsController.php` | **NOVO** |
| `resources/views/social/analytics.blade.php` | **NOVO** |
| `resources/views/layouts/app.blade.php` | link Analytics em 3 role blocks |
| `routes/partials/social.php` | rota `/social/analytics` (grupo `auth`+`subscription`, name `social.analytics.index`) |
| `scripts/insights-test.php` | **NOVO** — diagnóstico manual |

---

## 5. Commits relevantes

- **`db14834`** fix(social-metrics): constroi PAGEID_OBJECTID manual (tentativa que se provou insuficiente pra posts orfãos)
- **`91fa66c`** fix(social-publisher): posts com foto em 2 fases (upload + attach ao feed) — **causa raiz resolvida**

Anteriores (implementação inicial da feature) — vários commits do dia com criação de service, command, controller, view e menu.

---

## 6. Estado dos posts existentes vs futuros

| Cenário | Vai coletar métricas? |
|---|---|
| Posts publicados **antes** de `91fa66c` (ex: #10) | Não — órfãos permanentes. O sync detecta (`is_orphan`), anula `facebook_post_id` (preservando o id original em `error_message`) e para de re-tentar. Sem isso, cada órfão geraria warning hourly por 30 dias. |
| Posts publicados **depois** de `91fa66c` (fluxo 2 fases) | Sim — FB Insights básico (impressions/likes/clicks) funciona |
| Comments/shares/reactions no FB | Só depois de aprovar `pages_read_user_content` |
| IG (impressions/reach/likes/comments/saved) | Só depois de aprovar `instagram_manage_insights` |

---

## 7. Como testar após deploy

```bash
# 1) No VPS, atualizar + aplicar migration (OBRIGATÓRIO antes de qualquer sync)
cd /var/www/vivensi && git pull origin main
sudo -u www-data php artisan migrate --force
# confirmar: 2026_07_29_184645_add_extra_metrics_to_post_metrics deve constar como Ran
sudo -u www-data php artisan migrate:status | grep extra_metrics

# 2) Publicar UM POST NOVO pela UI (/social/posts)
#    (posts antigos são órfãos — não vão retornar dados)

# 3) Testar coleta imediata (não esperar 1h do scheduler)
sudo -u www-data php artisan posts:sync-metrics --days=1

# 4) Diagnóstico do último post (mostra response cru da Meta)
sudo -u www-data timeout 30 php scripts/insights-test.php

# 5) Ver dashboard
#    → https://vivensi.app.br/social/analytics?days=7
```

**Resultado esperado após post novo:**

- `facebook_post_id` gravado no formato `PAGEID_POSTID` (com underscore)
- `sync-metrics` retorna `1 OK` (não `0 OK, 1 falha`)
- Dashboard mostra impressions/likes/clicks reais em ~1h (Meta demora pra popular)
- Comments/shares aparecem como 0 até aprovar `pages_read_user_content`
- IG aparece como 0 até aprovar `instagram_manage_insights`

---

## 8. Pendências (próximo App Review)

1. Submeter `instagram_manage_insights` → destrava métricas do Instagram
2. Submeter `pages_read_user_content` → destrava comments/shares/reactions do Facebook
3. Reenviar `business_management` (planejado 26/09) — não bloqueia analytics mas é do Meta Apps roadmap
