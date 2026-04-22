# 🛸 DOSSIÊ DE ENTREGA: PROJETO VIVENSI (ABRIL 2026)

## 📌 1. STATUS DA INFRAESTRUTURA
- **Servidor:** AWS Lightsail (4GB RAM / 2 vCPUs / Ubuntu 22.04).
- **Domínio:** `vivensi.app.br` operando em **conexão direta** (DNS-only).
- **Segurança:** SSL ativo (HTTPS) via Let's Encrypt (Certbot).
- **Banco de Dados:** `SESSION_DRIVER=database`.

## 💻 2. ESTADO DO CÓDIGO
- **Ambiente:** `.env` configurado e funcional. `APP_DEBUG` desativado.
- **Super Admin Master:** Role `super_admin` com controle total do painel.
- **Multi-Tenancy:** `BelongsToTenant` trait com `withoutGlobalScopes()` aplicado em Jobs e Webhooks.

## 🤖 3. HUB DE MENSAGERIA & IA

### Bruce AI (Bot de Atendimento ao Cliente) ✅ ATIVO
- Webhook: `POST /api/evo/webhook/{token}` (por instância/tenant)
- Processamento: `ProcessEvolutionWebhook` → `ProcessWhatsappAiResponse`
- IA: Google Gemini ou DeepSeek (configurável por tenant)
- Treinamento: Painel `/whatsapp/settings` (Nome, Missão, FAQ, Tom)

### Vivensi Command Bot (Bot de Gestão Interna) ⏳ AGUARDANDO NÚMERO
- Webhook: `POST /api/whatsapp/bot`
- Controller: `app/Http/Controllers/Api/WhatsAppBotController.php`
- Funcionalidades por role:
  - **super_admin / ngo:** Saldo, Tarefas, Registrar Atendimento, Consultar Beneficiário
  - **manager:** Saldo, Tarefas, Concluir Tarefa, Lançar Despesa
  - **employee:** Saldo, Tarefas, Concluir Tarefa, Lançar Despesa (c/ aprovação)
  - **comum:** Saldo Pessoal, Tarefas, Lançar Receita, Lançar Despesa

## 🔧 4. PENDÊNCIAS DO COMMAND BOT (aguardando número)

Quando o novo número estiver disponível, executar na ordem:

### Passo 1 — Atualizar .env no servidor
```env
WHATSAPP_BOT_INSTANCE=vivensi-bot   # nome da instância na Evolution API
WHATSAPP_BOT_PHONE=55XXXXXXXXXXX    # novo número (formato E.164 sem +)
```

### Passo 2 — Cadastrar o número no usuário Super Admin
```bash
php artisan tinker
```
```php
App\Models\User::withoutGlobalScopes()
    ->where('email', 'EMAIL_DO_SUPER_ADMIN')
    ->update(['phone' => '55XXXXXXXXXXX']);
```

### Passo 3 — Criar instância na Evolution API
- Acessar `https://evo.vivensi.app.br`
- Criar instância com nome `vivensi-bot`
- Conectar o novo número via QR Code

### Passo 4 — Configurar Webhook na Evolution API
- Instância: `vivensi-bot`
- URL do Webhook: `https://vivensi.app.br/api/whatsapp/bot`
- Evento: `messages.upsert`

### Passo 5 — Testar
- Enviar "oi" para o novo número
- O bot deve responder com o menu de Super Admin

## 📋 5. OUTROS RECURSOS

- **E-mails:** Integrado via `BrevoService` (API Transactional).
- **Real-time:** Pusher/Echo configurado no frontend.
- **Pagamentos:** OpenPix (Rifas), PagSeguro (em testes).

## 🚀 6. PRÓXIMOS PASSOS SUGERIDOS
1. Ativar o Command Bot com o novo número (ver Seção 4).
2. Expandir o Command Bot com comandos estruturados diretos (ex: "DESP: 150 | Combustível").
3. Expandir o `EvolutionApiService` para suporte a envio de PDF e imagens (`sendMedia`).

---
*Atualizado em 22/04/2026*
