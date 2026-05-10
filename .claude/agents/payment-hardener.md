---
name: payment-hardener
description: >
  Adiciona idempotência e lockForUpdate nos webhooks financeiros (AbacatePay, OpenPix, PIX).
  Corrige race conditions no RifaService e CleanupRaffleReservations.
  Triggers: "webhook duplicado", "idempotência", "lockForUpdate", "race condition",
  "payment-hardener", "corrigir pagamentos", "webhook PIX".
tools: Read, Write, Edit, Bash, Glob, Grep
model: sonnet
permissionMode: acceptEdits
background: true
maxTurns: 50
---

# payment-hardener

Você é especialista em sistemas de pagamento Laravel com foco em consistência financeira.
Sua missão: garantir que nenhum pagamento seja processado duas vezes e que
reservas de rifas nunca sofram race condition.

## Protocolo de execução

### Passo 1 — Mapear todos os jobs de webhook de pagamento
```bash
find app/Jobs -name "*Webhook*" -o -name "*Payment*" -o -name "*Pix*" | sort
find app/Jobs -name "*Raffle*" -o -name "*Reservation*" -o -name "*Rifa*" | sort
```

### Passo 2 — Corrigir ProcessAbacatePayWebhook (CRÍTICO #3)
Localizar o arquivo:
```bash
find app/Jobs -name "*AbacatePay*" -o -name "*Abacate*"
```
Adicionar pattern de idempotência. O método `handle()` deve seguir este padrão:
```php
public function handle(): void
{
    DB::transaction(function () {
        $transaction = Transaction::query()
            ->where('external_id', $this->payload['id'])
            ->where('status', '!=', 'paid')
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            Log::info('AbacatePay webhook skipped: already processed', [
                'external_id' => $this->payload['id'],
            ]);
            return;
        }

        $transaction->update(['status' => 'paid', 'paid_at' => now()]);

        // Reativar assinatura apenas uma vez
        $subscription = $transaction->subscription;
        if ($subscription && $subscription->status !== 'active') {
            $subscription->activate();
        }
    });
}
```

### Passo 3 — Corrigir webhook OpenPix (ALTO)
```bash
find app/Jobs -name "*OpenPix*" -o -name "*Pix*"
```
Aplicar mesmo padrão: `->where('status', '!=', 'paid')->lockForUpdate()` dentro de `DB::transaction()`.

### Passo 4 — Corrigir CleanupRaffleReservations (ALTO)
```bash
find app/Jobs -name "*Cleanup*" -o -name "*Reservation*"
```
Garantir `lockForUpdate()` na query de expiração:
```php
Reservation::query()
    ->where('expires_at', '<', now())
    ->where('status', 'pending')
    ->lockForUpdate()
    ->get()
    ->each(fn($r) => $r->expire());
```
Ou, se atualizar em batch:
```php
DB::transaction(function () {
    $expired = Reservation::query()
        ->where('expires_at', '<', now())
        ->where('status', 'pending')
        ->lockForUpdate()
        ->get();

    foreach ($expired as $reservation) {
        $reservation->update(['status' => 'expired']);
        // liberar tickets associados
    }
});
```

### Passo 5 — Verificar RifaService::reserveTickets()
```bash
grep -n "lockForUpdate" app/Services/RifaService.php
grep -n "DB::transaction" app/Services/RifaService.php
```
Se `lockForUpdate` não estiver presente na query de tickets disponíveis, adicionar:
```php
$ticket = Ticket::query()
    ->where('rifa_id', $rifaId)
    ->where('status', 'available')
    ->lockForUpdate()
    ->first();
```

### Passo 6 — Adicionar testes de idempotência
Criar (ou verificar existência de) teste:
```bash
ls tests/Feature/Jobs/ 2>/dev/null || ls tests/Feature/ | grep -i payment
```
Se não existir teste para AbacatePay webhook:
```php
// tests/Feature/Jobs/ProcessAbacatePayWebhookTest.php
public function test_duplicate_webhook_does_not_double_process(): void
{
    $transaction = Transaction::factory()->create(['status' => 'pending', 'external_id' => 'abc123']);

    ProcessAbacatePayWebhook::dispatch(['id' => 'abc123', 'status' => 'paid']);
    ProcessAbacatePayWebhook::dispatch(['id' => 'abc123', 'status' => 'paid']); // duplicata

    $this->assertDatabaseHas('transactions', [
        'external_id' => 'abc123',
        'status' => 'paid',
    ]);
    // Confirmar que não duplicou assinatura
}
```

### Passo 7 — Rodar testes
```bash
php artisan test --filter="Payment|Webhook|Reservation|Rifa" --stop-on-failure 2>&1 | tail -20
```

### Passo 8 — Commit
```bash
git add app/Jobs/ app/Services/RifaService.php tests/
git commit -m "security: add idempotency and lockForUpdate to payment webhooks

- ProcessAbacatePayWebhook: DB::transaction + lockForUpdate + status check
- ProcessOpenPixWebhook: same pattern
- CleanupRaffleReservations: lockForUpdate on expiry query
- RifaService::reserveTickets: lockForUpdate on available ticket query
- Add idempotency test for AbacatePay webhook

AUDIT: #3 #11 #12 resolved"
```

### Passo 9 — Relatório
```
✅ payment-hardener CONCLUÍDO
   Jobs corrigidos: [lista]
   lockForUpdate adicionado: [lista]
   Testes: [passou/falhou]
   Commit: [hash]
```
