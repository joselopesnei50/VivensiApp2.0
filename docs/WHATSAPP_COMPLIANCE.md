# WhatsApp — Compliance, risco regulatório e plano de migração

> Tarefa 3.4 da auditoria (`PROMPT_CORRECAO_VIVENSI.md`).
> Documento de posicionamento técnico, jurídico e de produto.
> Última atualização: 2026-06-12.

---

## TL;DR

A Vivensi opera hoje com **dois backends de WhatsApp**:

| Backend | Tipo | Endossado pela Meta? | Risco de banimento | Custo |
|---|---|---|---|---|
| Evolution API (Baileys) | Não-oficial | **NÃO** — viola ToS | **Alto e inevitável** | Apenas infraestrutura |
| Meta Cloud API | Oficial | **SIM** | Próximo de zero por ToS | Por conversa iniciada |

A operação atual está **majoritariamente em Baileys**. Toda a política antiban (`AntiBanManager`, warming, simulação de digitação, spintax, janelas horárias) é **mitigação de risco em uma plataforma que a Meta explicitamente proíbe** — não eliminação. **Cliente sob Baileys pode ser banido a qualquer momento sem aviso, independente da proteção.**

Este documento define a posição da Vivensi sobre essa realidade e o plano de evolução.

---

## 1. O que é Baileys / Evolution API

Evolution API é um wrapper sobre a biblioteca **Baileys**, que implementa o protocolo interno do WhatsApp Web por engenharia reversa. O número conecta como "dispositivo vinculado" via QR code, similar ao WhatsApp Web no navegador.

**Por que existe:** historicamente, antes da Meta lançar a Cloud API com preços acessíveis (2022), Baileys era o único caminho para automação programática barata.

**Por que a Meta proíbe:**

- [WhatsApp Business Terms](https://www.whatsapp.com/legal/business-terms) — uso comercial via canais não oficiais é vedado.
- A Meta pode detectar conexões Baileys via padrões de tráfego e banir o número sem aviso.
- O ban pode ser por número, por conta de WhatsApp Business, ou em casos graves por CNPJ inteiro (com efeito sobre outros números do mesmo grupo).

**Por que continua funcionando hoje:**

- A detecção é probabilística, não absoluta.
- Para detectar, a Meta precisa achar padrão suspeito (volume, cadência, conteúdo, denúncia de destinatário).
- A política antiban da Vivensi (`AntiBanManager`) atrasa a detecção fazendo o tráfego parecer humano — mas não a impede.

**Quem assume o risco no modelo atual:** o cliente final (a ONG, a empresa) — o número WhatsApp deles é banido, não o da Vivensi.

---

## 2. O que é WhatsApp Cloud API (oficial)

Lançada pela Meta em 2022, é o caminho oficial e suportado para envio programático em escala.

**Como funciona:**

- O número precisa ser **registrado no Meta Business Manager** e passar por verificação.
- Mensagens iniciadas pelo negócio (broadcast/notificação) usam **templates pré-aprovados** pela Meta. Templates podem ser rejeitados.
- Mensagens dentro de janela de 24h após o cliente responder são livres em conteúdo.
- Existe o feature de **Coexistência** (lançada em 2024) que permite o app WhatsApp Business no celular do operador continuar funcionando junto com a Cloud API no mesmo número.

**Custo:**

- Por conversa iniciada (não por mensagem). Janela de 24h conta como uma conversa.
- Tabela de preços por país e categoria (utility / marketing / authentication / service). Brasil em 2026 fica entre R$ 0,05 e R$ 0,40 por conversa, dependendo da categoria.
- Conversas iniciadas pelo cliente (não pelo negócio) são gratuitas.

**Risco de banimento por uso correto:** próximo de zero. Para banir, o negócio precisaria violar políticas explícitas (spam de fato, golpe, conteúdo proibido) — não é por "volume alto" como no Baileys.

---

## 3. Comparação direta

| Critério | Baileys / Evolution | Meta Cloud API |
|---|---|---|
| Custo direto | R$ 0 por mensagem | R$ 0,05–0,40 por conversa iniciada pelo negócio |
| Custo de risco | Ban inesperado a qualquer momento | Risco residual mínimo |
| Setup | QR code, segundos | Verificação de empresa + número, 1–10 dias |
| Volume sustentável | Limitado por warming (ex: 370/dia chip novo) | Sem teto técnico, regulado por templates |
| Templates | Mensagem livre | Templates aprovados para iniciar conversa |
| Mídia | Suporte direto | Suporte direto |
| Histórico de chat | Vivensi armazena | Vivensi armazena |
| Coexistência com celular | Multi-device do WhatsApp | Coexistência oficial da Meta (mais robusta) |
| Suporte da Meta | Não existe | Existe (canal oficial) |
| Termos de uso violados? | **Sim** | Não |
| Recomendado para cliente novo? | Não | Sim |

---

## 4. Posicionamento da Vivensi

O posicionamento honesto, decidido a partir desta auditoria, é:

1. **Para clientes NOVOS contratados a partir desta data**, **Cloud API é o backend padrão recomendado**. Baileys segue disponível, mas só com **termo de uso assinado pelo cliente reconhecendo o risco de ban** (modelo de termo em rascunho — pendente revisão jurídica antes de ir para a UI de contratação).

2. **Para clientes JÁ contratados em Baileys**, mantemos o serviço funcionando enquanto a política antiban continuar segurando. Não vamos forçar migração — mas vamos:
   - Avisar formalmente sobre o risco (email institucional + aviso na UI do módulo WhatsApp)
   - Oferecer migração assistida para Cloud API sem custo de setup
   - Documentar no contrato a impossibilidade de garantia contra ban no backend não-oficial

3. **A política antiban (`AntiBanManager`) continua sendo desenvolvida** como mitigação para os clientes em Baileys, mas é tratada internamente como **redução de probabilidade**, não eliminação. Comentários no código já refletem essa leitura (`PROMPT_CORRECAO_VIVENSI.md` Tarefa 3.4).

4. **A consolidação do pipeline legado de campanhas** (ver `BACKLOG_WHATSAPP_CONSOLIDATION.md`) deve ser feita já considerando Cloud API como destino canônico, não Baileys.

---

## 5. Plano de migração para Cloud API

Os pré-requisitos técnicos e de negócio para habilitar Cloud API estão em [`ROADMAP_APROVACAO_META.md`](../ROADMAP_APROVACAO_META.md). Em resumo:

1. **CNPJ ativo + Verificação de Empresa no Business Manager** (Vivensi como provedora)
2. **Domínio `vivensi.app.br` verificado**
3. **App Business no developers.facebook.com** com permissões `whatsapp_business_management` + `whatsapp_business_messaging` aprovadas
4. **Embedded Signup** habilitado (botão "Conectar com Facebook" oficial)

A base técnica de código já existe parcialmente (`WhatsappController` com handshake Meta). Falta:

- [ ] Concluir o processo de aprovação Meta (timeline estimada: 3–10 dias úteis após submissão completa, pode estender)
- [ ] Implementar fluxo de gerenciamento de templates pré-aprovados na UI do tenant
- [ ] Adaptar `ProcessBroadcastCampaignJob` para usar a Cloud API quando o tenant escolher esse backend
- [ ] Migrar `EvolutionApiService::sendMessage` para um Strategy pattern que delega para Cloud ou Evolution conforme tenant config
- [ ] Página pública de privacidade (`/privacidade`) explicando uso de dados de WhatsApp — **pré-requisito da Meta** para aprovação

---

## 6. Termo de uso para clientes em Baileys (rascunho — revisão jurídica pendente)

> **Rascunho não-jurídico.** Antes de incluir em contrato ou UI, validar com advogado especializado em direito digital / contratos SaaS.

Sugestão de cláusula:

> O CONTRATANTE reconhece que o serviço de envio de mensagens WhatsApp opera, na modalidade "Pacote Padrão", através de canal técnico **não autorizado oficialmente** pela Meta Platforms, Inc. ("WhatsApp Web não-oficial via biblioteca Baileys/Evolution API"). Em decorrência disso:
>
> (a) O número WhatsApp utilizado pode ser **banido, suspenso ou restrito pela Meta a qualquer tempo, sem aviso prévio**, independentemente das medidas técnicas de mitigação adotadas pela CONTRATADA.
>
> (b) A CONTRATADA emprega política técnica de redução de risco ("AntiBanManager") — incluindo limites de volume, janelas horárias, simulação de cadência humana e detecção de sinais de banimento — mas **não garante e não pode garantir** ausência de banimento.
>
> (c) Em caso de banimento, a CONTRATADA **não se responsabiliza** por perda de comunicação, perda de contatos, perda de oportunidade de negócio, ou qualquer dano direto ou indireto decorrente.
>
> (d) Para serviço com **garantia contra banimento por uso correto**, é oferecida a modalidade alternativa "Pacote Cloud Oficial", baseada na **WhatsApp Cloud API** da Meta, com tarifação por conversa iniciada e regras de conteúdo definidas pela própria Meta.
>
> O CONTRATANTE declara ciência das condições acima e opta livremente pela modalidade do "Pacote Padrão" / "Pacote Cloud Oficial" (assinalar).

---

## Referências

- [WhatsApp Business Terms](https://www.whatsapp.com/legal/business-terms) — Meta
- [WhatsApp Cloud API Pricing](https://developers.facebook.com/docs/whatsapp/pricing) — Meta for Developers
- [WhatsApp Business API Coexistence (2024)](https://developers.facebook.com/docs/whatsapp/cloud-api/coexistence/overview) — Meta for Developers
- [`ROADMAP_APROVACAO_META.md`](../ROADMAP_APROVACAO_META.md) — Vivensi, pré-requisitos de aprovação
- [`BACKLOG_WHATSAPP_CONSOLIDATION.md`](../BACKLOG_WHATSAPP_CONSOLIDATION.md) — Vivensi, consolidação dos pipelines
- [`PROMPT_CORRECAO_VIVENSI.md`](../PROMPT_CORRECAO_VIVENSI.md) — Vivensi, auditoria de segurança (Tarefa 3.4)
- `app/Services/Messaging/AntiBanManager.php` — Vivensi, mitigação técnica
