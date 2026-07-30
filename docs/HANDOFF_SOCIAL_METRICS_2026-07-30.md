# HANDOFF — Métricas Sociais (Facebook + Instagram)

**Data:** 2026-07-30
**Para:** próximo agente/sessão que pegar este trabalho
**Doc técnico completo:** `docs/SOCIAL_METRICS_2026-07-30.md` (ler primeiro — arquitetura, bugs da Meta v22 e como foram resolvidos)

---

## 1. Onde estamos (resumo em 5 linhas)

1. Feature de coleta de métricas de posts (FB + IG) está **implementada e commitada** até `91fa66c` (HEAD da main local).
2. A causa raiz dos posts sem métricas era o `POST /photos published=true` da Meta v22 **não criar post no feed** — resolvido com publicação em 2 fases (commit `91fa66c`).
3. O código foi **auditado em 2026-07-30**: todas as afirmações do doc técnico batem com o código real. Duas imprecisões do doc foram corrigidas (local da rota + passo de migrate).
4. **Falta o teste de fogo real**: publicar 1 post novo com foto no VPS e confirmar que métricas chegam.
5. Permissões Meta pendentes limitam o que coleta hoje (ver §4).

## 2. Mudanças LOCAIS ainda NÃO commitadas (feitas em 2026-07-30)

| Arquivo | O quê |
|---|---|
| `app/Services/MetaSocialInsightsService.php` | Órfãos agora são **desativados permanentemente**: quando `is_orphan` é detectado, o id vai pra `error_message` e `facebook_post_id` vira `null` (post sai da query do sync hourly; IG do mesmo post continua sincronizando). Antes: warning hourly por 30 dias por órfão. Lint OK (`php -l`). |
| `docs/SOCIAL_METRICS_2026-07-30.md` | Corrigido: rota está em `routes/partials/social.php` (não `web.php`); seção 7 agora inclui `php artisan migrate --force` como passo obrigatório; seção 6 documenta o auto-desligamento de órfãos. |
| `docs/HANDOFF_SOCIAL_METRICS_2026-07-30.md` | Este arquivo. |

**Ação:** commitar (sem Co-Authored-By) e fazer deploy antes de testar.

## 3. Checklist de deploy/teste no VPS (nesta ordem)

```bash
cd /var/www/vivensi && git pull origin main
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan migrate:status | grep extra_metrics   # deve estar Ran
# CRÍTICO: a migration 2026_07_29_184645 adiciona clicks/saved/engagement/source
# em post_metrics. Se não rodar, o sync quebra com coluna inexistente.

# Publicar 1 POST NOVO COM FOTO pela UI (/social/posts) — posts antigos são órfãos
sudo -u www-data php artisan posts:sync-metrics --days=1
sudo -u www-data timeout 30 php scripts/insights-test.php   # response cru da Meta
# Dashboard: https://vivensi.app.br/social/analytics?days=7
```

**Sucesso =** `facebook_post_id` no formato `PAGEID_POSTID` (com underscore) + sync retorna `1 OK` + impressions no dashboard em ~1h.

## 4. O que NÃO vai funcionar ainda (esperado, não é bug)

| Métrica | Motivo | Destrava quando |
|---|---|---|
| FB comments/shares/reactions | falta `pages_read_user_content` | próximo App Review |
| IG tudo (impressions/reach/likes/saved) | falta `instagram_manage_insights` | próximo App Review |
| FB reach | `post_impressions_unique` deprecated na Meta — hardcoded 0 | nunca (limitação Meta) |
| `business_management` | rejeitada; reenvio planejado 26/09 | não bloqueia analytics |

Funciona HOJE (com `pages_read_engagement` aprovada): FB impressions, likes, clicks.

## 5. Pontos de atenção conhecidos (não bloqueantes, avaliar depois)

1. **Card "Alcance" mostra 0** no dashboard (FB deprecated + IG sem permissão) — usuário final pode achar que é bug. Considerar esconder o card ou tooltip explicativo.
2. **Janelas divergentes no controller** (`SocialAnalyticsController`): `$totals` filtra por `scheduled_at` do post; `$byNetwork` filtra por `created_at` da linha de métrica. Se a 1ª sync de um post atrasar muito, os números podem divergir entre os blocos.
3. Fórmula de engagement difere por rede (FB inclui clicks; IG inclui saved) — intencional, mas não documentado na UI.

## 6. Mapa rápido dos arquivos

- Coleta: `app/Services/MetaSocialInsightsService.php` + `app/Console/Commands/SyncSocialMetrics.php` (hourly no `Kernel.php`)
- Publicação 2 fases: `app/Services/MetaSocialPublisherService.php` → `publishFacebookPhotoPost()`
- Dashboard: `app/Http/Controllers/SocialAnalyticsController.php` + `resources/views/social/analytics.blade.php`
- Rota: `routes/partials/social.php` (`social.analytics.index`, middleware `auth`+`subscription`)
- Models: `PostMetric` (hasMany por `source`) + `ScheduledPost::metricsTotal()`
- Migration pendente de confirmação no VPS: `2026_07_29_184645_add_extra_metrics_to_post_metrics.php`
- Diagnóstico manual: `scripts/insights-test.php`
