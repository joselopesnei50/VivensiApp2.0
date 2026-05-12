# Memorando Técnico: Módulo Social AI Hub - Vivensi App

**Data:** 12 de Maio de 2026
**Assunto:** Implementação do Módulo de Inteligência Artificial para Redes Sociais

---

## 1. Visão Geral
O módulo **Social AI Hub** foi desenvolvido para automatizar a criação de conteúdo estratégico. Ele integra inteligência artificial de texto (DeepSeek) e imagem (Together AI / FLUX.1) para entregar posts prontos para publicação, respeitando limites de uso e mantendo a performance do servidor.

## 2. Especificações Técnicas

### A. Modelagem de Dados
- **Tabela `ai_social_posts`**:
  - `id`, `tenant_id`, `user_id`, `title_theme`, `body_text`, `image_path`, `status`, `created_at`.
- **Tabela `ai_image_usage_logs`**:
  - `id`, `user_id`, `month_year`, `count`.

### B. Rotas (web.php)
| Método | Rota | Nome | Descrição |
| :--- | :--- | :--- | :--- |
| GET | `/social-ai` | `social-ai.index` | Lista rascunhos e exibe dashboard. |
| POST | `/social-ai/generate` | `social-ai.generate` | Inicia geração assíncrona do post. |
| DELETE | `/social-ai/{post}` | `social-ai.destroy` | Exclui um rascunho do sistema. |

### C. Camada de Serviço e Background
- **`SocialAIContentService`**: Gerencia a lógica de negócio e integrações de API.
- **`GenerateSocialPostJob`**: Job em background que realiza as chamadas pesadas de IA e download de mídia para o storage.
- **`SystemSetting`**: As chaves de API são consultadas dinamicamente no banco de dados (`deepseek_api_key`, `together_ai_api_key`).

## 3. Regras de Negócio e Segurança
1. **Cota Mensal**: Limite rígido de **60 imagens por usuário/mês**. A validação ocorre antes de iniciar o processamento.
2. **API Keys**: Gerenciadas via painel Super Admin. Nenhuma chave sensível é exposta ou buscada do `.env`.
3. **Multi-tenancy**: Filtro obrigatório por `tenant_id` em todas as consultas.
4. **Storage**: As imagens são salvas em `storage/app/public/ai_posts/` e servidas via URL pública após o `storage:link`.

## 4. Manual de Ativação (Produção)
1. Rodar migrations: `php artisan migrate`.
2. Configurar chaves no Painel Admin:
   - DeepSeek API Key.
   - Together AI API Key.
3. Garantir que as filas (Queue) estejam rodando: `php artisan queue:work`.
4. Linkar storage: `php artisan storage:link`.

---
*Implementado por Antigravity AI - Vivensi Development Team.*
