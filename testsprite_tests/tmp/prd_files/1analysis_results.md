# 🛡️ Relatório de Auditoria de Segurança: Vivensi
**Objetivo:** Transição para Ambiente de Teste Real (Produção)
**Arquitetura:** Laravel, Evolution API v2, Reverb, PostgreSQL/MySQL

Após varredura no código-fonte do Vivensi focando nos pontos levantados, identifiquei **vulnerabilidades críticas** que devem ser corrigidas antes de liberar o sistema para usuários reais.

---

## 1. Camada de Redução de Superfície de Ataque (Webhooks & API)

> [!CAUTION]
> **Risco Crítico: Ausência de Validação de Assinatura**
> O `EvolutionWebhookController` protege a rota `/api/evo/webhook/{token}` verificando a existência do token no banco, o que evita enumeração. No entanto, **não há verificação da assinatura global (`Webhook-Signature` ou `apikey`)**. Um atacante que descubra ou intercepte um único `{token}` pode realizar um ataque de POST flood na fila do sistema forjando mensagens.
> **O mesmo ocorre na rota `/api/whatsapp/bot`** (tratada pelo `WhatsAppBotController`), que está completamente exposta na internet sem nenhuma autenticação de cabeçalho.

- **Proteção CSRF:** As rotas estão declaradas no `$except` do `VerifyCsrfToken` (e residem no `routes/api.php`, sem o middleware `web`). Isto está correto arquiteturalmente, mas exige compensação com autenticação (Token JWT/Signature), que está ausente no momento.
- **Sanitização de Input (Stored XSS):** No `WhatsAppBotController`, funções como `processExpense` e `processAttendance` extraem dados de texto (`$description`) diretamente da string do WhatsApp e inserem no banco: `$transaction->description = $description;`. Se um usuário mal-intencionado enviar `DESP: 100 | <script>alert('XSS')</script>`, esse código será salvo e, ao ser exibido no painel de administração da ONG, poderá ser executado no navegador do Gestor. **Correção:** Utilizar `strip_tags()` ou HTML Purifier antes de salvar no DB.

## 2. Camada de Isolamento Multi-tenant (Segurança de Dados)

> [!WARNING]
> **Risco Alto: Falha de Autorização no WebSocket (Reverb)**
> Em `routes/channels.php`, apenas os canais `App.Models.User.{id}` e `notifications.{id}` estão autorizados. No entanto, o evento `InstanceStatusChanged` tenta fazer o broadcast no canal privado `PrivateChannel('tenant.' . $this->tenantId . '.whatsapp')`. Como essa rota não existe no `channels.php`, as requisições de subscrição do frontend receberão erro **403 Forbidden**. Se no futuro for aberta com lógica frouxa, uma ONG poderá interceptar status de instâncias de outra ONG.

- **Vazamento de Escopo (Queries):** A maioria dos controllers usa `$query->where('tenant_id', $user->tenant_id)` corretamente. No entanto, o `WhatsAppBotController` usa frequentemente `Task::withoutGlobalScopes()->where('tenant_id', ...)` e o mesmo para `Beneficiary`. Embora funcione, desativar Global Scopes em controllers aumenta a chance de erro humano. **A correção definitiva** é garantir que a trait `BelongsToTenant` atue automaticamente via Middleware e sessão do usuário.

## 3. Resiliência Operacional e Anti-Abuso

> [!IMPORTANT]
> **Risco de Banimento na Meta (WhatsApp)**
> A diretiva pediu para revisar a classe `AntiBanManager`. **Esta classe não existe no projeto.** No momento, a classe `EvolutionApiService` insere um delay estático e muito curto (ex: `1200ms` padrão) em `sendMessage()`. Enviar mensagens em massa ou processar muitas requisições com intervalos fixos e sem respeitar janela de horário causará **banimento sumário das contas mestres** pela Meta.

- **Rate Limiting (Throttle):** Os limites configurados em `routes/api.php` (`throttle:300,1` e `throttle:500,1`) são excessivamente permissivos. Permitir 500 requisições por minuto na rota de Webhook abre margem para negação de serviço (DoS) no worker do Queue. O ideal é reduzir para algo como `60,1` (por IP) e bloquear tráfego fora da ASN da infraestrutura da Evolution API.
- **Shell Injection & Arquivos:** O `EvolutionApiService::sendMedia()` confia cegamente no `mimetype` e `mediaBase64` injetado pelo usuário. É necessário que o backend faça a validação da integridade real do Base64 (verificando os magic bytes), para que o servidor da Evolution API não engasgue processando payloads corrompidos ou maliciosos.

## 4. Monitoramento e Infraestrutura

- **Painel Horizon:** ✅ **Seguro.** O acesso no `HorizonServiceProvider` está bloqueado por um Gate que valida `$user->role === 'super_admin'`.
- **Métricas de Erro:** Não foram encontrados Listeners observando falhas de fila. Quando o `ProcessEvolutionWebhook` falhar mais de 3 vezes, a falha vai silenciar. Recomenda-se adicionar o hook `failed()` nos jobs críticos para acionar um alerta por e-mail ou no dashboard.

---

### 📝 Plano de Ação Prioritário (Sugerido para hoje)

1. Criar um **Middleware de Assinatura (`VerifyEvolutionSignature`)** e plugar nas rotas `/api/evo/webhook/{token}` e `/api/whatsapp/bot`.
2. Incluir `strip_tags()` nos payloads de descrição processados pelo `WhatsAppBotController`.
3. Registrar a autorização para `tenant.{tenantId}.whatsapp` no arquivo `routes/channels.php`.
4. Criar a classe **`AntiBanManager`** para injetar tempos de `delay` randômicos (ex: 4s a 15s) dependendo do volume na `EvolutionApiService`.

**Deseja que eu inicie o desenvolvimento e a correção desses 4 pontos prioritários do Plano de Ação?**
