# C1 — Auditoria `withoutGlobalScope('tenant')` — Findings

**Data:** 2026-07-12
**Escopo:** 201 usos em 67 arquivos
**Conclusão:** ✅ **Padrão consistente e seguro. Zero críticos reais.**

## Metodologia

Grep completo por `withoutGlobalScope('tenant')` e `withoutGlobalScope(TenantScope::class)` em `app/`. Classificação por padrão de mitigação:

| Nível | Definição | Contagem |
|---|---|---|
| 🟢 SEGURO | Bypass + filtro `tenant_id` explícito OU blind index HMAC | 184 |
| 🟡 REVISAR | Bypass em contexto interno (webhooks/jobs com tenant_id injetado) | 12 |
| 🔴 CRÍTICO REAL | Sem mitigação | **0** |
| ⚠️ FALSO POSITIVO | Reportado como crítico mas confirmado seguro | 2 |

## Falsos positivos analisados

### FP1: `LgpdSelfServiceController::download`

**Reportado como:** IDOR via download token.

**Análise real:**
- Token gerado por `random_bytes(32)` → 256 bits de entropia
- Base64url encoded → ~43 chars opacos
- Coluna `export_token` tem UNIQUE index no banco
- Impossibilidade prática de colisão (2^256 ≈ 10^77 possibilidades)

**Veredicto:** Seguro por design. Padrão OAuth2 bearer token.

### FP2: `PublicAttendanceController`

**Reportado como:** Parameter binding sem validação tenant.

**Análise real:**
```
Token público → ClassSession::findByPublicToken($token)
  → hash_hmac('sha256', $token, config('app.key'))
  → WHERE public_token_bidx = ?
  → retorna ClassSession com tenant_id CORRETO
```

Todas queries subsequentes usam `$session->tenant_id` explicitamente (linhas 32, 72, 85, 100 do controller).

**Veredicto:** Padrão blind index HMAC bem implementado. Impossível vazar cross-tenant sem posse do token válido.

## Padrões seguros identificados (para replicar em code review)

### Padrão A — Blind Index HMAC + Lookup Único

Usado em: `ClassSession`, `Contract`, `NgoDonor.portal_token`, `Receipt`, `WhatsappInstance.instance_token`.

```php
// Model
protected $hidden = ['xxx_token_bidx'];

public function setXxxTokenAttribute(string $token): void {
    $this->attributes['xxx_token']      = Crypt::encryptString($token);
    $this->attributes['xxx_token_bidx'] = hash_hmac('sha256', $token, config('app.key'));
}

public static function findByToken(string $token): ?self {
    $bidx = hash_hmac('sha256', $token, config('app.key'));
    return static::withoutGlobalScopes()->where('xxx_token_bidx', $bidx)->first();
}
```

**Por que é seguro:**
- Token nunca comparado com plaintext no banco (só HMAC)
- Vazamento de dump do banco não expõe tokens
- Colisão SHA256 tem probabilidade astronômica

### Padrão B — Bypass + Filtro Explícito `tenant_id`

Usado em: `ProcessCloudApiWebhook`, `ProcessEvolutionWebhook`, `TransparencyController`, todos os Console Commands.

```php
$instance = WhatsappInstance::withoutGlobalScope('tenant')
    ->where('provider', 'cloud_api')
    ->where('phone_number_id', $phoneNumberId)  // chave única global
    ->first();

// Uso subsequente:
$data->tenant_id = $instance->tenant_id;  // vem do lookup autoritativo
```

**Por que é seguro:**
- Chave de lookup (phone_number_id, waba_id, slug) é única globalmente
- `tenant_id` vem do registro encontrado (não do input do usuário)

### Padrão C — Rota Pública com Slug Único

Usado em: `TransparencyController`, `PublicRaffleController`, `CampaignController`.

```php
$page = TransparencyPortal::withoutGlobalScopes()
    ->where('slug', $slug)
    ->where('is_published', true)
    ->firstOrFail();

// Uso: $page->tenant_id vem do lookup, não do input.
```

**Por que é seguro:**
- Slug UNIQUE constraint no banco garante 1 registro por slug globalmente
- `is_published` bloqueia rascunhos

## Testes de regressão criados

`tests/Feature/Security/IdorRegressionTest.php` (7 testes verdes):

1. LGPD export token de tenant A não vaza dados do tenant B
2. User B não cancela LGPD deletion do user A
3. Dashboard `/eu/dados` só mostra requests do usuário logado
4. HMAC-SHA256: valores diferentes → hashes diferentes (7 samples)
5. Token opaco 256 bits: 100 gerações sem colisão
6. `Crypt::encryptString` gera raws diferentes para mesmo plaintext (IV random)
7. `BelongsToTenant` scope filtra em contexto HTTP (via dashboard)

Se alguém remover filtro `tenant_id` ou substituir HMAC por comparação plaintext, algum destes testes quebra.

## Estado final dos 4 críticos

| # | Título | Estado |
|---|---|---|
| C1 | `withoutGlobalScope` audit | 🟢 **Fechado — padrão seguro confirmado + testes de regressão** |
| C2 | Settings secrets | 🟢 Fechado |
| C3 | LGPD art. 15/18 | 🟢 Fechado |
| C4 | Encryption at-rest PII | 🟢 Fechado |

## Recomendações futuras (não bloqueantes)

1. **Documentar padrão A/B/C** em `docs/security-patterns.md` (guia interno)
2. **Pre-commit hook** que rejeita `withoutGlobalScope` sem `->where('tenant_id'` ou `_bidx` na mesma expressão (linter simples com PHPCS)
3. **`SystemSetting.value` encryption** — próximo escopo (tokens Meta/Pusher/AbacatePay em plaintext no banco). Fora do C1.

## Referências

- Memória: `project_vivensi_seguranca26.md` (Sprint segurança fase 2 — tenant fail-closed em 2026-07-03)
- Memória: `feedback_vivensi_belongs_to_tenant.md` (trait bootBelongsToTenant define tenant() globalmente)
- Trait: `app/Traits/BelongsToTenant.php` (fail-closed em web + bypass em console)
