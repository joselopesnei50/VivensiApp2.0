# 🧠 Memorando de Transição: Vivensi App 2.0 (MODERNIZADO)

Este documento serve como a "memória central" do projeto, consolidando as recentes evoluções e a mudança estratégica de infraestrutura.

---

## ✅ 1. O que foi Concluído Recentemente (Modernização)
*   **Central de Comando (Dashboards):** Redesign completo com estética High-End, Glassmorphism e suporte total a **Dark Mode (F5)**.
*   **Onboarding Guiado (F1):** Implementação do Tour interativo (Shepherd.js) para novos usuários.
*   **Busca Global (Ctrl+K):** Sistema de navegação rápida por todo o ERP integrada ao Topbar (F6).
*   **Exportação de Elite (F4):** Geração de relatórios em **PDF Profissional** e **CSV** via streams.
*   **Sincronização em Tempo Real (F2):** Implementação do **Soketi** (Self-hosted WebSocket) para notificações instantâneas.
*   **Gestão de APIs via Admin:** Migração das chaves de Pusher, Z-API e AI do `.env` para o banco de dados (`system_settings`), permitindo trocas dinâmicas pelo Super Admin.

---

## 🚀 2. O Grande Próximo Objetivo: WhatsApp OFICIAL (Meta)
**Mudança de Rota:** Abandonamos a Evolution/Z-API para adotar a **WhatsApp Business API (Cloud API da Meta)**. Isso garante 100% de blindagem contra banimentos e uma imagem corporativa de elite.

### Infraestrutura Oficial (Meta Cloud):
*   **Sem Celular Conectado:** O número fica hospedado na nuvem da Meta. Zero dependência de bateria ou conexão estável de aparelhos físicos.
*   **Escopo Global:** Uso de **WABA (WhatsApp Business Account)** e **Phone Number ID**.
*   **Mensageria Híbrida:** 
    *   *Janela de 24h:* Conversas livres suportadas pelo **Bruce AI**.
    *   *Templates:* Mensagens iniciadas pela ONG via modelos pré-aprovados pela Meta.

### Estratégia de IA (Bruce AI + Meta):
*   **Webhook Seguro:** O Laravel recebe eventos diretamente da Meta e processa via `ProcessWhatsappAiResponse`.
*   **Prompt Customizado:** O cliente define o "treinamento" (personalidade) da IA diretamente pelo painel.

---

## 🛠️ 3. Próximos Passos (Checklist)
1.  [ ] **Finalizar o Webhook Meta:** Consolidar a recepção de mensagens vindas da Cloud API.
2.  [ ] **Módulo de Templates:** Criar interface para o cliente escolher e enviar modelos aprovados pela Meta.
3.  [ ] **Billing Integration:** Monitorar o uso da API para repasse de custos ou gestão de créditos.
4.  [ ] **Migração Total:** Substituir os resquícios de provedores não-oficiais (`EvolutionApiService`, `ZApiService`) pela nova `MetaApiService`.

---

**Assunto para retomar:** "Nei, memorizei a mudança para a **API Oficial da Meta**. O Command Center já está modernizado e sincronizado. Vamos focar agora na finalização do Webhook Oficial?"

---
**Arquivos de Referência Atuais:**
- `resources/views/whatsapp/settings.blade.php` (Configuração Meta)
- `app/Jobs/ProcessWhatsappAiResponse.php` (Cérebro da IA)
- `app/Http/Controllers/AdminSettingsController.php` (Gestão de Chaves)
- `MEMORANDO_TECNICO_MIGRACAO.md` (Detalhes de infra)
