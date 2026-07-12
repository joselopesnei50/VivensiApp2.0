# Auditoria de Segurança Vivensi — 2026-07-11

Escaneamento em 3 camadas: backend (auth/autoriza/injection), frontend
(headers/CSP/DevTools) e infra (secrets/encryption/LGPD).

**Estado geral: 7.5/10** — boa base, 4 críticos precisam de ação.

## Heatmap por camada

| Camada | Nota | Estado |
|---|---|---|
| Auth + 2FA + Session | 8/10 | 🟢 Sólido (super_admin forçado, TOTP, recovery) |
| Autorização multi-tenant | 6/10 | 🟡 Global scope OK, mas 154 `withoutGlobalScope` sem audit |
| Headers HTTP + CSP | 9/10 | 🟢 SecurityHeaders completo |
| CSRF + XSS | 8/10 | 🟢 Purifier + Blade escape |
| Frontend/DevTools | 8/10 | 🟢 Settings já mascarado (padrão value="") |
| Webhooks | 9/10 | 🟢 HMAC + hash_equals em Meta/AbacatePay/Evolution |
| Encryption at rest | 5/10 | 🔴 Só WhatsApp cifra; Lead/User PII plaintext |
| LGPD compliance | 5/10 | 🔴 Modelos existem, endpoints art. 15/18 ausentes |
| Secrets management | 6/10 | 🟡 SystemSetting persiste tokens sem cifra |
| Rate limiting | 7/10 | 🟡 API OK, faltam password reset e outros |

---

## 🚨 Críticos

### C1. `withoutGlobalScope('tenant')` sem auditoria (154 arquivos)

**Vetor:** IDOR em massa. Bypass do global scope sem `where('tenant_id')` explícito
subsequente vaza dados cross-tenant.

**Suspeitos:** `TransparencyController`, `PublicRaffleController`,
`OpenPixWebhookController`, todos controllers `Public*`.

**Fix:** Auditar cada uso; considerar helper que exige tenant_id explicitamente:
```php
public static function withoutTenantScopeAuditado(int $tenantId, Closure $callback)
{
    return static::withoutGlobalScope('tenant')
        ->where('tenant_id', $tenantId)
        ->tap($callback);
}
```

Prioridade: **alta**. Estimativa: 2-3 dias de audit.

### C2. API Keys em /admin/settings — ✅ JÁ CORRIGIDO

Auditoria inicial reportou API keys expostas em `<input type="password">`. Ao
inspecionar o código, **todos os 15 inputs usam `value=""`** e o controller
apenas retorna `$xxx_configured` (bool). Padrão implementado corretamente pelo
dev original.

**Ação tomada em 2026-07-11:** adicionado test suite de regressão
(`tests/Feature/Admin/AdminSettingsSecurityTest.php`) + comentário defensivo no
controller (`AdminSettingsController::index`). Garante que qualquer regressão
futura será detectada pelo CI.

Prioridade: **fechado** (rede de segurança em vigor).

### C3. LGPD art. 15 (deleção) + art. 18 (exportação) não implementados

Model `LgpdDataRequest` existe mas sem controller/UI. ANPD já multou empresas
brasileiras em 2024-2025 por ausência desses fluxos.

**Fix:**
- `/lgpd/exportar-meus-dados` → retorna ZIP com JSON de tudo (Lead, User,
  interações WhatsApp, agendamentos, pagamentos)
- `/lgpd/excluir-conta` → soft delete + hard delete + purge de logs em 30 dias
- Ambos autenticados + confirmação por email token
- Middleware LGPD registra cada request em `AuditLog`

Prioridade: **alta**. Estimativa: 1 sprint.

### C4. Encryption at rest incompleta para PII

**Já cifrado:**
- ✅ `WhatsappInstance.graph_access_token`, `instance_token` (Crypt::encryptString)
- ✅ `User.two_factor_secret`, `two_factor_recovery_codes` (encrypted casts)
- ✅ `Beneficiary.cpf/nis` (HMAC-SHA256 blind index)

**Plaintext:**
- ❌ `Lead.phone`, `Lead.email` (sensível LGPD)
- ❌ `User.phone`
- ❌ `SystemSetting.value` para tokens Meta/Pusher/AbacatePay

**Fix:** Aplicar mesmo pattern do WhatsappInstance + blind index se queries
`WHERE phone = ?` forem necessárias. Migration + mutator + backfill dos existentes.

Prioridade: **média-alta**. Já há memória "Criptografia at-rest pendente" —
esta é a hora de finalizar.

---

## ⚠️ Altos

| # | Item | Local |
|---|---|---|
| A1 | `{!! !!}` em blog admin com Quill — verificar Purifier no backend | `resources/views/admin/blog/create.blade.php` |
| A2 | Rate limit ausente: `/password/reset`, `/register`, opt-in público | `routes/*.php` |
| A3 | 2FA opcional pra managers e NGOs (só super_admin forçado) | `RequireTwoFactor:35` |
| A4 | Verificar histórico git do `.env` (rotacionar se apareceu) | — |
| A5 | Session cookie SameSite=Lax → considerar Strict em admin | `config/session.php` |
| A6 | `APP_DEBUG` sem guard no deploy.sh | `deploy.sh` |

---

## 🖥️ DevTools — o que atacante consegue

### ❌ NÃO consegue (proteções ativas)

| Ataque | Bloqueio |
|---|---|
| Injetar `<script>` malicioso | CSP `script-src 'self'` |
| Roubar cookie de sessão | `httpOnly: true` |
| MITM downgrade HTTPS | HSTS max-age=1 ano |
| Clickjacking iframe | X-Frame-Options: SAMEORIGIN |
| CSRF cross-origin | SameSite=Lax + CSRF token |
| MIME sniff | X-Content-Type-Options: nosniff |
| Ler JS original | Zero source maps em `public/js/*` |
| Escalar privilégio removendo `disabled` | Backend valida role em cada rota |

### ⚠️ Consegue (precisa correção)

| Ataque | Como | Fix |
|---|---|---|
| Enumerar tenants (IDOR) | Trocar ID em URL | C1 |
| Enumerar usuários | Password reset com resposta diferente | A2 |

### ℹ️ Sobre "bloquear DevTools"

**Não é recomendado.** DevTools não pode ser bloqueado de forma confiável (F12
disable é bypassável em <30s via ctrl+shift+i, menu, chrome://inspect, etc).
Tentativas quebram acessibilidade.

Defesa correta: **assumir cliente hostil**, validar tudo no server, nunca
colocar segredo no HTML. É o que Vivensi já faz.

---

## ✅ 12 boas práticas já em vigor

1. SecurityHeaders middleware completo (CSP + HSTS + X-Frame + Referrer)
2. HMAC verification em webhooks (`hash_equals` timing-safe)
3. Bcrypt rounds=10 para senhas
4. 2FA TOTP + recovery codes cifrados
5. HTMLPurifier com allowlist restrita
6. CSRF em todas rotas web
7. CORS com origin específico (não `*`)
8. Login com mensagem genérica (sem enumeração)
9. Rate limit /login (5/15min por email+IP)
10. LoginActivity com IP/UA rastreado
11. Blind index HMAC-SHA256 em Beneficiary.cpf
12. Multi-tenant global scope automático

---

## 📋 Roadmap sugerido

**Sprint 1 (7-10 dias) — Encryption + LGPD:**
- C4: Cifrar Lead.phone/email + User.phone
- C3: Endpoints LGPD art. 15 + 18

**Sprint 2 (5-7 dias) — Audit + Rate limit:**
- C1: Auditar 154 `withoutGlobalScope`
- A2: Rate limit em password reset, register, opt-in
- A3: Forçar 2FA para managers com acesso a PII

**Sprint 3 (3-5 dias) — Hardening:**
- A1: Confirmar Purifier no BlogController::store
- A4: Verificar histórico `.env`
- A5: Session cookie SameSite=Strict em admin
- A6: `APP_DEBUG=false` guard no deploy

---

## Referências

- `tests/Feature/Admin/AdminSettingsSecurityTest.php` — regression suite C2
- `app/Http/Controllers/AdminSettingsController.php` — comentário defensivo
- Auditoria prévia: `AUDIT_FOLLOWUP_2026-06-15.md` (memoria)
- Sprint seguranca26 (2026-07-03): Fases 1+3+2 deployadas (Purifier, dev
  portal, tenant fail-closed)
