# 🧠 Memorando de Transição: Vivensi App 2.0

Este documento serve como a "memória central" para a próxima sessão de desenvolvimento, garantindo que o Agente Antigravity saiba exatamente onde paramos após a atualização do sistema.

---

## ✅ 1. O que foi Concluído Recentemente
*   **Normalização de Cargos (Roles):** Corrigido o erro onde novos usuários eram criados como "Colaborador" (mismatch entre `ngo_admin`/`project_manager` e `ngo`/`manager`).
*   **Gestão de Equipe (NGO):** Implementado o modal de edição em `/ngo/team`, permitindo que o administrador altere o nome, cargo e status dos membros.
*   **Acesso VIP/Admin:** Corrigidos os menus laterais e acessos para Super Admin e administradores de ONG.
*   **Deploy AWS:** Sincronizado o ambiente de produção (`/var/www/vivensi`) com o comando manual:
    ```bash
    sudo git fetch origin main && sudo git reset --hard origin/main && sudo php artisan optimize:clear
    ```

---

## 🚀 2. O Grande Próximo Objetivo: Migração WhatsApp
Estamos migrando do motor Z-API para a **Evolution API v2** visando escalabilidade SaaS e proteção anti-banimento.

### Infraestrutura Planejada (AWS):
*   **VPS Independente:** Criar uma nova instância na AWS (EC2 ou Lightsail) para rodar a Evolution API via Docker.
*   **Setup:** Ubuntu 22.04 + Docker + Redis + Evolution API v2.

### Estratégia Técnica (Anti-Ban):
1.  **Spintax:** Variar o texto das mensagens usando o padrão `{Olá|Oi|Fala}`.
2.  **Delays Randômicos:** Intervalos de 15 a 45 segundos entre disparos em massa.
3.  **Presença Simulada:** Disparar estados de "Digitando..." ou "Gravando áudio..." segundos antes do envio real.
4.  **Multi-Tenant:** Cada cliente terá sua própria instância Docker gerenciada dinamicamente pelo Laravel.

---

## 🛠️ 3. Próximos Passos (Checklist para o Antigravity)
1.  [ ] **Apoio no Setup da VPS:** Orientar o Nei na instalação do Docker e levantamento da imagem da Evolution API.
2.  [ ] **Criação do `EvolutionApiService`:** Desenvolver o novo serviço de integração no Laravel.
3.  [ ] **Migrations de Campanha:** Criar as tabelas `campaigns`, `campaign_messages` e `bot_sessions`.
4.  [ ] **Refatoração do Webhook:** Adaptar o `WhatsappController` para receber os eventos da Evolution.
5.  [ ] **Limpeza de Legado:** Assim que o único cliente online for migrado, deletar os resquícios da Z-API.

---

**Assunto para retomar:** "Nei, estou com o Memorando de Transição aberto. Vamos começar o Faseamento 0: Setup da VPS AWS para a Evolution API?"

---
**Arquivos de Referência:**
- `app/Http/Controllers/WhatsappController.php`
- `app/Services/ZApiService.php` (Para referência e posterior deleção)
- `implementation_plan.md` (Na pasta .gemini/brain)
