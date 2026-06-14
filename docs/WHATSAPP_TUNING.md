# WhatsApp — Tuning antiban por instância

> Tarefa 3.2 da auditoria (`PROMPT_CORRECAO_VIVENSI.md`).
> Documenta como ajustar perfil de warming e limite horário por instância
> WhatsApp sem alterar código nem rodar migration.
> Última atualização: 2026-06-13.

---

## Visão geral

Antes da Tarefa 3.2, três valores estavam hardcoded em `AntiBanManager`:

- Perfil de warming (14 dias, 20→370/dia)
- Limite horário (`MAX_PER_HOUR = 55`)
- Restrição padrão ao detectar ban (`24h`)

Agora vêm de `config/whatsapp.php` (defaults idênticos) com **override por instância** via campo JSON `whatsapp_instances.settings`. Comportamento default não mudou.

## Perfis de warming disponíveis

Definidos em `config/whatsapp.php` em `antiban.warming_profiles`:

### `default` (mesmo de sempre)
| Dia | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12 | 13 | 14 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Limite/dia | 20 | 30 | 40 | 55 | 70 | 90 | 115 | 140 | 170 | 205 | 245 | 290 | 340 | 370 |

Crescimento ~30% ao dia. Após dia 14, warming desativa e a instância usa `daily_limit`.

### `conservative` (para chip novo ou cliente cauteloso)
| Dia | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12 | 13 | 14 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Limite/dia | 15 | 20 | 25 | 35 | 45 | 55 | 65 | 80 | 95 | 110 | 125 | 135 | 145 | 150 |

Crescimento ~25% ao dia, teto final 60% menor que o `default`. Indicado quando:
- Número WhatsApp comprado há menos de 30 dias
- Cliente já teve ban em chip anterior
- Setup com IP de VPS novo (ainda não "reconhecido" pela Meta)
- Tenant pede a proteção máxima possível

## Ativando perfil conservador em uma instância específica

Via Tinker no VPS (até a UI existir):

```bash
cd /var/www/vivensi && php artisan tinker
```

No prompt `>`:

```php
$instance = \App\Models\WhatsappInstance::find(ID_DA_INSTANCIA);
$settings = $instance->settings ?? [];
$settings['warming_profile'] = 'conservative';
$instance->update(['settings' => $settings]);
```

Para voltar ao perfil default:

```php
$instance = \App\Models\WhatsappInstance::find(ID_DA_INSTANCIA);
$settings = $instance->settings ?? [];
unset($settings['warming_profile']);
$instance->update(['settings' => $settings]);
```

## Override do limite horário

Mesma lógica do warming, via `settings['max_per_hour']`:

```php
$instance = \App\Models\WhatsappInstance::find(ID_DA_INSTANCIA);
$settings = $instance->settings ?? [];
$settings['max_per_hour'] = 25;   // menos agressivo que o default 55
$instance->update(['settings' => $settings]);
```

**Observação:** este valor é o teto do rate limiter horário, **independente** do warming. Mesmo em warming, o limite horário continua valendo — qual for o menor (warming ou horário) vence.

## Override por env (afeta todos os tenants)

Para mudar o default global do sistema (ex.: cliente novo já entra mais cauteloso por padrão), edite `.env` do VPS:

```
WHATSAPP_MAX_PER_HOUR=30
WHATSAPP_BAN_RESTRICTION_HOURS=48
```

Depois:

```bash
cd /var/www/vivensi && php artisan view:clear
sudo systemctl reload php8.1-fpm
```

**Esse caminho mexe em todos os tenants ao mesmo tempo.** Use com cuidado.

## Verificando o que está ativo numa instância

```php
$instance = \App\Models\WhatsappInstance::find(ID);
$ab = new \App\Services\Messaging\AntiBanManager(new \App\Services\EvolutionApiService($instance));

[
    'max_per_hour'    => $ab->getMaxPerHour($instance),
    'warming_profile' => $ab->getWarmingProfile($instance),
    'warming_day'     => $ab->getWarmingDay($instance),
    'warming_active'  => $ab->isWarming($instance),
    'restricted'      => $ab->isInstanceRestricted($instance),
];
```

## Onde NÃO há override

`getBanRestrictionHours()` (restrição quando isBanSignal detecta ban) é **global por design**. Permitir cada tenant decidir quanto tempo a instância dele fica restrita após sinal de ban seria operacionalmente ruim — tenant teria incentivo a setar `1 hora` e re-incidir, o que prejudica a reputação da Vivensi com a Meta como provedora.

## Adicionando novos perfis

Edite `config/whatsapp.php` no array `antiban.warming_profiles`. Por exemplo, para um perfil "ultra agressivo" (não recomendado mas suportado):

```php
'aggressive' => [
    1=>40, 2=>60, 3=>85, 4=>115, 5=>150, 6=>190, 7=>235,
    8=>285, 9=>340, 10=>400, 11=>465, 12=>535, 13=>610, 14=>700,
],
```

Restart do PHP-FPM depois pra `config()` pegar:

```bash
sudo systemctl reload php8.1-fpm
```

E ative numa instância via `settings['warming_profile'] = 'aggressive'`.

## Limites técnicos

- Cada perfil precisa ter chaves consecutivas começando em `1`
- Último dia do perfil deve ser ≤ `MAX_PER_HOUR * 24` ou o limite horário vai sempre dominar
- Valores não-int ou ≤0 são ignorados — fallback para `default`

## Referências

- `app/Services/Messaging/AntiBanManager.php` — implementação
- `config/whatsapp.php` — defaults
- [`docs/WHATSAPP_COMPLIANCE.md`](WHATSAPP_COMPLIANCE.md) — contexto regulatório
- [`PROMPT_CORRECAO_VIVENSI.md`](../PROMPT_CORRECAO_VIVENSI.md) — auditoria (Tarefa 3.2)
