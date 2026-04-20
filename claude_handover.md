# 🛸 DOSSIÊ DE ENTREGA: PROJETO VIVENSI (ABRIL 2026)

## 📌 1. STATUS DA INFRAESTRUTURA
- **Servidor:** Migrado para AWS Lightsail (4GB RAM / 2 vCPUs / Ubuntu 22.04).
- **Domínio:** `vivensi.app.br` operando em **conexão direta** (DNS-only) para o IP `34.193.132.112`.
- **Segurança:** SSL ativo (HTTPS) via Let's Encrypt (Certbot).
- **Banco de Dados:** Otimizado com `SESSION_DRIVER=database` para evitar travamento de disco por micro-arquivos.

## 💻 2. ESTADO DO CÓDIGO (CLEANUP)
- **Cloudflare:** Removidos todos os middlewares e headers que geravam loops de redirecionamento (522/524).
- **Ambiente:** `.env` configurado e funcional. `APP_DEBUG` desativado (segurança).
- **Acesso:** Criado Super Admin Master (Role: `super_admin`) para controle total do painel.

## 🤖 3. HUB DE MENSAGERIA & IA
- **WhatsApp (Evolution API v2):** 
    - Atualmente integrado via `EvolutionApiService.php`.
    - Suporta: Criação de instâncias, QR Code, Pairing Code e Envio de Texto com Spintax.
    - **Oportunidade v2:** A biblioteca da Evolution v2 permite agora Integração Nativa com Typebot, Dify e Chatwoot que ainda não foram totalmente exploradas no código atual.
- **E-mails:** Integrado via `BrevoService` (API Transactional).
- **Real-time:** Configurado para Pusher/Echo no frontend.

## 🛠️ 4. PENDÊNCIAS IMEDIATAS (PRÓXIMOS PASSOS)
1.  **Configurações:** O usuário precisa inserir as chaves de API (Gemini, Evolution, Brevo e Pusher) no painel `admin/settings` (já acessível via Super Admin).
2.  **Backups:** Necessário configurar rotina de backup do banco de dados na AWS.
3.  **Mídia:** Expandir o `EvolutionApiService` para suportar envio de PDFs e Imagens (`sendMedia`).

---
*Gerado automaticamente para transferência de contexto.*
