# AUDIT FOLLOWUP — 2026-06-04

> **Documento de complemento ao `AUDIT_SUMMARY.md` (10/05/2026).**
> Resultado da revalidação dirigida dos 22 itens críticos/altos do summary,
> conduzida em produção em 04/jun/2026.

---

## Veredito

| Severidade | Total | Fechado | Documentado como aceitável | Aberto |
|---|---|---|---|---|
| 🔴 Críticos | 10 | 9 | 1 | 0 |
| ⚠️ Bloqueantes extras | 3 | 3 | 0 | 0 |
| 🟠 Altos | 10 | 8 | 2 | 0 |

**Status:** 🚦 **GO** para uso real em produção.

---

## Item #6 — User sem `BelongsToTenant` (marcado como ACEITÁVEL)

### Decisão

Item NÃO bloqueia GO. Será endereçado em sprint de hardening dedicada,
sem urgência.

### Evidência

Em 04/jun/2026 foi feito o mapa completo dos **95 usos de `User::` no
codebase** (`app/`). Categorização:

| Categoria | Comportamento | Avaliação |
|---|---|---|
| Filtra `tenant_id` explicitamente | ~35 call sites em ManagerController, TaskController, TeamController, TransactionController, ProjectController, DashboardController, HomeController, InternalChatController (contacts), FinanceService, ProjectHealthService, ProjectService, ProjectAlertService, NgoGrantDeadlineAlert, WarmCaches | ✅ Seguro com ou sem trait |
| Cross-tenant intencional (super admin) | `AdminController.*`, `Admin/AdminTeamController`, `Admin/EmailCampaignController`, `Admin/ExecutiveAgendaController`, `Admin/LgpdController`, `Admin/SalesPipelineController`, `MeetingBookingController` | ✅ Esperado — admin enxerga todos os tenants |
| Sem autenticação (trait não filtra) | `Auth/ForgotPasswordController`, `RegisterController`, `Api/WhatsAppBotController`, `PublicController` | ✅ Comportamento correto da trait quando `!Auth::check()` |
| `withoutGlobalScopes()` explícito | `Admin/BotController`, `ProjectAlertService` | ✅ Defense-in-depth correto |
| Console | `SyncUserRoles` (artisan) | ✅ Trait bypassa scope em console por design |
| `User::findOrFail` sem tenant filter + check manual subsequente | `InternalChatController:44, 75` — `if ($receiver->tenant_id !== $user->tenant_id) abort 403` | ✅ Defense-in-depth manual. Vaza apenas existência de `user_id` (não dados) |
| `User::query()` sem tenant filter | `ContractController:81, 232` — encadeia `->where('tenant_id', $contract->tenant_id)` em seguida | ✅ Seguro |

**Vetores REAIS de exploit encontrados: zero.**

Toda escrita em entidades tenant-scoped que recebe `user_id` do request
(`addMember`, `assigned_to`, etc.) já valida com
`Rule::exists()->where(tenant_id)` — implementado nos commits de
isolamento desta sessão (`469ccb5`, `14eb99e`, etc.).

### Por que NÃO adicionar a trait agora

- Exigiria adicionar `withoutGlobalScopes()` em ~15 lugares
  (`Admin/*`, auth, webhooks, public)
- Risco médio-alto de quebrar painel super admin em produção
- Ganho real hoje: zero exploit fechado, apenas rede de segurança contra
  código futuro

### Quando reavaliar

Adicionar o trait quando:
- Houver tempo dedicado para auditoria + testes manuais de cada call
  site
- OU surgir novo desenvolvedor regularmente tocando o codebase (rede de
  segurança vira valiosa)

---

## Item Alto A2 — `CleanupRaffleReservations` sem `lockForUpdate`

### Decisão

Deferido para backlog. **Não bloqueia GO.**

### Evidência

`app/Console/Commands/CleanupRaffleReservations.php` libera reservas de
rifas com `reserved_at` > 30 min sem `lockForUpdate()` na query. Race
condition possível apenas se:

- Múltiplos schedulers rodam simultaneamente (improvável — Supervisor
  configura `vivensi-scheduler` único)
- E exatamente no mesmo segundo um comprador estiver finalizando o
  pagamento da reserva que está sendo liberada

Impacto teórico: bilhete liberado a um segundo do pagamento confirmado
= comprador legítimo perde a vaga. Custo: aborrecimento, não dano
financeiro nem de dados.

Fix sugerido (futuro, ~3 linhas):
```php
DB::transaction(function () use ($cutoff) {
    RaffleTicket::where('status', 'pending')
        ->where('reserved_at', '<', $cutoff)
        ->lockForUpdate()
        ->update([
            'status' => 'available',
            'buyer_name' => null, ...
        ]);
});
```

---

## Decisões aceitas como estão (não são bug)

### A6 — Uploads de Banner e ScheduledPost em disco público

`BannerController:291` e `ScheduledPostController:49` armazenam em
`tenants/{id}/banners/uploads` e `tenants/{id}/social-media` no disco
**público**.

**Avaliação:** intencional. Banner é renderizado em landing pages
públicas; ScheduledPost vai para redes sociais. Ambos PRECISAM ser
acessíveis por URL. O isolamento de path por tenant já está correto.

**Diferente de:** `TransactionController` (anexos financeiros), que foi
movido para disco privado nesta sessão (`commit 6b3df7f`) porque nota
fiscal de doação NÃO deve ser publicamente acessível por LGPD.

### A9 — Wildcard `*` em `pestphp/pest-plugin-laravel`

`composer.json` linha 31. **Avaliação:** aceitável.

- Está em `require-dev`, **não roda em produção** (deploy.sh usa
  `composer install --no-dev`)
- Os 3 wildcards realmente perigosos do summary original
  (`doctrine/dbal`, `guzzlehttp/guzzle`, `open-pix/php-sdk`) **foram
  corrigidos** para pin de major version (`^3.10`, `^7.10`, `^1.1`).

---

## Hardening adicionais entregues nesta sessão (04/jun/2026)

Lista dos commits que avançaram itens não previstos no summary
original ou aprofundaram itens já listados:

| Commit | Item endereçado |
|---|---|
| `a5d8cb9`, `3ecb4de`, `19f32d7` | Fix UX — `ngo` bloqueado em show/kanban + classes de design system |
| `c733919` | Auth obrigatória em `generateSummary` (Bruce IA, prevenia disparo anônimo de job custoso) |
| `469ccb5` | Cross-tenant FK em `TransactionController::store` — `category_id` e `project_id` agora validam tenant |
| `14eb99e` | Mesma classe de fix em `storeGlobalPerson` e `importPeople` |
| `6b3df7f`, `0628e1a` | Anexos financeiros movidos para `storage/app/private/` + rota autenticada `transactions.attachment` (fix LGPD) |
| `034f90b` | Docblock de isolamento nos 5 webhook controllers (PagSeguro, ASAAS, AbacatePay, OpenPix, WhatsApp Meta) |
| `a8b8a03` | Throttle 30/min adicionado em GET `/r/{token}` e GET `/sign/{token}` |
| `a639351` | `public_receipt_token` e `contracts.token` migrados para encrypted-at-rest + blind index (mesmo padrão de `NgoDonor.portal_token`) |
| `3489ba0` | Módulo de Planejamento de Projetos (Fase 1) atrás de feature flag |
| `17f577f` | Fix cache stale em `BruceAiService::tenantContext` + bump de versão da chave de cache |

---

## Próxima auditoria

Sugerida após **30 dias de produção real** com usuários ativos.
Foco em:

- N+1 queries em dashboards reais (hoje com 2 transações não há sinal)
- Performance de queries em tabelas que crescem (transactions,
  project_logs, project_timeline_records, whatsapp_messages)
- Re-validação do AUDIT_SUMMARY itens médios (14 itens) que foram
  pulados nesta revisão
- Reavaliar #6 (User + `BelongsToTenant`) com tempo dedicado
- Reavaliar A2 (`CleanupRaffleReservations`) se houver reclamação de
  comprador
