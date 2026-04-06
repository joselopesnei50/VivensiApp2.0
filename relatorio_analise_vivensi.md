# Relatório de Análise do Sistema Vivensi

**Data:** 03/04/2026  
**Versão do Sistema:** Vivensi Laravel (Multi-tenancy SaaS)  
**Analista:** Assistente de Código Trae

## 1. Resumo Executivo

O sistema Vivensi é uma plataforma SaaS multi-inquilino desenvolvida em Laravel 9.x, destinada a gestão de projetos sociais, ONGs e terceiro setor. A análise abrangeu segurança, arquitetura, design, banco de dados e isolamento de usuários.

**Principais Conclusões:**
- Arquitetura bem estruturada com isolamento de inquilinos via trait `BelongsToTenant`
- Vulnerabilidade crítica: Rotas de login/logout excluídas da proteção CSRF
- Configuração de CORS permissiva (`allowed_origins: ['*']`) em ambiente de produção
- Sistema de assinatura e controle de acesso por subscription implementado
- Boas práticas de validação de entrada e consultas parametrizadas
- Necessidade de revisão das configurações de ambiente e secrets

## 2. Arquitetura do Sistema

### 2.1 Stack Tecnológico
- **Backend:** Laravel 9.x com PHP
- **Frontend:** Blade templates, JavaScript vanilla
- **Banco de Dados:** MySQL com migrações estruturadas
- **Autenticação:** Laravel Sanctum para APIs, sessões para web
- **Multi-tenancy:** Isolamento por `tenant_id` em todas as tabelas principais

### 2.2 Estrutura de Diretórios
```
app/
├── Models/           # 50+ modelos Eloquent com trait BelongsToTenant
├── Http/
│   ├── Controllers/ # Controladores organizados por funcionalidade
│   ├── Middleware/  # Middlewares personalizados (TrackUserActivity, CheckSubscription)
│   └── Kernel.php   # Configuração de middlewares
├── Traits/          # Traits reutilizáveis (BelongsToTenant, Auditable)
└── Services/        # Serviços de negócio (BrevoService, etc.)
```

### 2.3 Fluxo de Autenticação
1. Login via formulário Blade com token CSRF
2. Middleware `CheckSubscription` valida status da assinatura
3. Middleware `TrackUserActivity` registra última atividade
4. Redirecionamento baseado em role (super_admin, manager, employee, user)

## 3. Análise de Segurança

### 3.1 Vulnerabilidades Críticas

#### **VULNERABILIDADE 1: CSRF Desabilitado para Login/Logout**
- **Arquivo:** `app/Http/Middleware/VerifyCsrfToken.php`
- **Problema:** As rotas `login` e `logout` estão excluídas da verificação CSRF
- **Impacto:** Possibilidade de ataques CSRF em endpoints de autenticação
- **Risco:** Alto - Permite que atacantes forcem login/logout de usuários
- **Solução:** Remover `login` e `logout` do array `$except`

#### **VULNERABILIDADE 2: Configuração CORS Excessivamente Permissiva**
- **Arquivo:** `config/cors.php`
- **Problema:** `allowed_origins` configurado como `['*']`
- **Impacto:** Qualquer domínio pode fazer requisições à API
- **Risco:** Médio-Alto em produção
- **Solução:** Restringir origens permitidas aos domínios da aplicação

#### **VULNERABILIDADE 3: Chaves de API em Configurações**
- **Arquivo:** `config/services.php`, `config/whatsapp.php`
- **Problema:** Chaves de API podem estar expostas se .env não estiver protegido
- **Impacto:** Acesso não autorizado a serviços terceiros (WhatsApp, Asaas, Brevo)
- **Risco:** Médio
- **Solução:** Garantir que todas as chaves estejam no .env e esteja fora do versionamento

### 3.2 Proteções Implementadas

#### **Proteções Efetivas:**
1. **Validação de Entrada:** Uso de Form Requests e validações no controller
2. **Sanitização de Consultas:** Consultas Eloquent parametrizadas, uso limitado de raw queries
3. **Middleware de Autenticação:** `auth`, `subscription`, `role-based` access control
4. **Proteção CSRF:** Habilitada para rotas web (exceto exceções mencionadas)
5. **Hash de Senhas:** Bcrypt por padrão do Laravel
6. **Tokens Únicos:** UUID para tokens públicos de recibos

#### **Middlewares de Segurança:**
- `VerifyCsrfToken`: Proteção CSRF (com exceções)
- `CheckSubscription`: Controle de acesso por assinatura
- `CheckLandingPageLimit`: Limite de criação de landing pages
- `TrackUserActivity`: Auditoria de atividade de usuários

## 4. Análise de Banco de Dados e Multi-tenancy

### 4.1 Estrutura de Multi-tenancy

#### **Modelo de Dados:**
- **Tabela `tenants`:** Armazena informações de cada organização (nome, documento, tipo, status de assinatura)
- **Coluna `tenant_id`:** Presente em todas as tabelas de dados (users, projects, transactions, tasks, etc.)
- **Trait `BelongsToTenant`:** Filtro global que automaticamente adiciona `WHERE tenant_id = ?` a todas as consultas

#### **Isolamento Implementado:**
```php
// No trait BelongsToTenant
static::addGlobalScope('tenant', function (Builder $builder) {
    if (app()->runningInConsole()) {
        return;
    }
    if (Auth::check()) {
        $user = Auth::user();
        if ($user && $user->role !== 'super_admin') {
            $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
        }
    }
});
```

#### **Exceções ao Isolamento:**
- **super_admin:** Pode acessar dados de todos os tenants
- **Comandos Artisan:** Filtro desabilitado quando runningInConsole()

### 4.2 Esquema do Banco de Dados

#### **Tabelas Principais:**
1. **users:** Usuários com roles (super_admin, manager, employee, user)
2. **tenants:** Organizações/clientes do sistema
3. **projects:** Projetos sociais/ONGs
4. **transactions:** Transações financeiras (income/expense)
5. **tasks:** Tarefas associadas a projetos
6. **whatsapp_*:** Integração com WhatsApp API
7. **subscription_plans:** Planos de assinatura

#### **Integridade Referencial:**
- Chaves estrangeiras configuradas com `onDelete('cascade')` ou `onDelete('set null')`
- Índices em colunas frequentemente consultadas (`tenant_id`, `project_id`, `status`)

### 4.3 Auditoria e Logs
- **Tabela `audit_logs`:** Registro de alterações em modelos com trait `Auditable`
- **Campos:** user_id, event, auditable_type, auditable_id, old_values, new_values, ip_address
- **Logs de WhatsApp:** Tabelas dedicadas para auditoria de mensagens

## 5. Análise de Design e Estrutura

### 5.1 Padrões de Projeto Identificados

#### **Padrões Implementados:**
1. **Repository Pattern:** Uso de models Eloquent como repositórios
2. **Service Pattern:** Serviços para lógica complexa (BrevoService, WhatsAppService)
3. **Trait Pattern:** Reutilização de comportamentos (BelongsToTenant, Auditable)
4. **Middleware Pattern:** Interceptação de requisições para validações

#### **Organização de Código:**
- **Controllers:** Um controller por entidade principal
- **Models:** Relações Eloquent bem definidas
- **Views:** Blade templates organizados por funcionalidade
- **Routes:** Agrupadas por middleware (web, auth, subscription)

### 5.2 Frontend e UX

#### **Tecnologias Frontend:**
- Blade templates com Bootstrap 5
- JavaScript vanilla para interatividade
- Chart.js para gráficos
- DataTables para tabelas

#### **Problemas de Design Identificados:**
1. **Painel do Gestor de Projetos:** Colunas desorganizadas (requer reorganização)
2. **"Impacto Geosocial":** Atualmente no Painel do Gestor, deveria estar no Painel do Terceiro Setor
3. **Sistema de Agenda:** Ausente no Painel ONG (tarefa pendente)

### 5.3 Sistema de Módulos
O sistema possui múltiplos módulos interconectados:
1. **Gestão de Projetos:** Criação, acompanhamento, métricas
2. **Financeiro:** Transações, orçamento, recibos públicos
3. **WhatsApp:** Chatbot, campanhas, automação
4. **Transparência:** Portal público, documentos
5. **Academia:** Cursos, certificados
6. **Voluntários:** Gestão de voluntariado

## 6. Vulnerabilidades Identificadas

### 6.1 Críticas (Alta Prioridade)

| Vulnerabilidade | Localização | Impacto | Recomendação |
|----------------|-------------|---------|--------------|
| CSRF desabilitado para login/logout | VerifyCsrfToken.php | Ataques de autenticação | Remover exceções |
| CORS excessivamente permissivo | config/cors.php | Ataques cross-origin | Restringir origens |
| Chaves API potencialmente expostas | .env.example | Acesso a serviços terceiros | Revisar .env real |

### 6.2 Médias (Média Prioridade)

| Vulnerabilidade | Localização | Impacto | Recomendação |
|----------------|-------------|---------|--------------|
| Session lifetime 120 minutos | config/session.php | Tempo de sessão longo | Reduzir para 30-60 min |
| Super_admin sem limitações | BelongsToTenant trait | Acesso a todos os dados | Implementar auditoria rigorosa |
| Uso de raw queries | Vários controllers | Potencial SQL injection | Revisar todas as queries |

### 6.3 Baixas (Baixa Prioridade)

| Vulnerabilidade | Localização | Impacto | Recomendação |
|----------------|-------------|---------|--------------|
| Falta de rate limiting | - | Ataques de força bruta | Implementar throttle |
| Logs detalhados em produção | - | Exposição de informações | Configurar níveis de log |

## 7. Recomendações de Melhoria

### 7.1 Segurança (Imediato)
1. **Corrigir CSRF:** Remover `login` e `logout` do array `$except` em `VerifyCsrfToken.php`
2. **Restringir CORS:** Configurar `allowed_origins` com domínios específicos da aplicação
3. **Revisar .env:** Garantir que arquivo .env real não esteja versionado e contenha secrets adequados
4. **Reduzir tempo de sessão:** Ajustar `SESSION_LIFETIME` para 30-60 minutos

### 7.2 Banco de Dados (Curto Prazo)
1. **Backup automático:** Implementar backup regular da base de dados
2. **Criptografia de dados sensíveis:** Considerar criptografia para CPF, dados pessoais
3. **Índices de performance:** Analisar queries lentas e adicionar índices necessários

### 7.3 Arquitetura (Médio Prazo)
1. **Implementar rate limiting:** Proteger endpoints de API contra abuso
2. **Auditoria completa:** Expandir sistema de logs para todas as operações críticas
3. **Testes automatizados:** Desenvolver suite de testes unitários e de integração
4. **Documentação de API:** Documentar endpoints para integrações futuras

### 7.4 Design e UX (Tarefas Pendentes)
1. **Reorganizar Painel do Gestor:** Corrigir colunas desorganizadas no Painel do Gestor de Projetos
2. **Mover "Impacto Geosocial":** Transferir funcionalidade para Painel do Terceiro Setor
3. **Implementar sistema de agenda:** Desenvolver agenda e distribuição de tarefas para equipe no Painel ONG

## 8. Conclusão

O sistema Vivensi apresenta uma arquitetura sólida e bem estruturada para um SaaS multi-inquilino do terceiro setor. A implementação do isolamento de dados via trait `BelongsToTenant` é eficaz e segue boas práticas de segurança.

**Pontos Fortes:**
- Isolamento de dados entre tenants bem implementado
- Sistema de assinatura e controle de acesso robusto
- Boa organização de código e separação de responsabilidades
- Múltiplos módulos integrados de forma coesa

**Pontos Fracos Críticos:**
- Vulnerabilidade de CSRF em endpoints de autenticação
- Configuração de segurança excessivamente permissiva (CORS)
- Necessidade de revisão de configurações de ambiente

**Próximos Passos Recomendados:**
1. **Prioridade 1:** Corrigir vulnerabilidade de CSRF (login/logout)
2. **Prioridade 2:** Revisar e restringir configurações de CORS
3. **Prioridade 3:** Implementar as melhorias de design solicitadas (reorganização de painéis)
4. **Prioridade 4:** Desenvolver sistema de agenda para Painel ONG

O sistema tem potencial para ser uma plataforma segura e eficiente para gestão de projetos sociais, necessitando principalmente de ajustes nas configurações de segurança e conclusão das funcionalidades pendentes de design.

---
**Assinatura:** Análise técnica completa do sistema Vivensi Laravel  
**Status:** Análise concluída - Aguardando implementação das correções