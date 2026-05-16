# Memorando Técnico: Módulo de Inteligência Territorial (IBGE)

**Data:** 12 de Maio de 2026
**Assunto:** Implementação da Integração com IBGE SIDRA e Localidades

---

## 1. Estrutura de Arquivos

### Backend (Lógica e Dados)
- **Model:** `app/Models/IbgeIndicatorCache.php` (Persistência de indicadores)
- **Service:** `app/Services/IBGEDataService.php` (Integração direta com APIs do IBGE)
- **Controller:** `app/Http/Controllers/SocialIndicatorController.php` (Orquestração e Integração Bruce AI)
- **Command:** `app/Console/Commands/SyncIBGEIndicators.php` (`php artisan vivensi:sync-ibge`)
- **Migration:** `database/migrations/2026_05_12_192707_create_ibge_indicators_cache_table.php`

### Frontend (Visual)
- **View:** `resources/views/intelligence/territorial.blade.php` (Dashboard v2.0)
- **Sidebar:** `resources/views/layouts/app.blade.php` (Link no menu lateral)

## 2. Rotas Registradas
- `GET  /intelligence/territorial` -> `intelligence.territorial` (Interface)
- `GET  /api/ibge/cities` -> `ibge.cities.search` (Busca de cidades)
- `GET  /api/ibge/indicators/{cityCode}` -> `ibge.indicators` (Processamento de dados)

## 3. Indicadores Monitorados (IBGE SIDRA)
- **População:** Tabela 4714 (Censo)
- **Renda:** Tabela 6407 (Rendimento Mensal)
- **Educação:** Tabela 1383 (Escolarização)
- **Saneamento:** Tabela 3218 (Esgotamento Sanitário)

## 4. Integração Bruce AI
O módulo utiliza a chave `deepseek_api_key` configurada no Painel Admin para gerar análises contextuais baseadas nos dados retornados pelo IBGE.

---
*Implementado por Antigravity AI - Vivensi Development Team.*
