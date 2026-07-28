# Roteiro do vídeo App Review — Meta (2026-07-28)

Vídeo único, um take, PT-BR com legendas em inglês. **Duração alvo: 5 min.**
Cobre as 6 permissões que serão submetidas juntas (business_management +
pages_show_list + pages_read_engagement + pages_manage_posts + instagram_basic
+ instagram_content_publish).

---

## Antes de gravar — checklist

- [ ] Fluxos migrados pro NC5HUBDIGITAL-EMP (ver `META_SOCIAL_MIGRATION_2026-07-28.md`)
- [ ] Você adicionado como Test User no painel do NC5HUBDIGITAL-EMP
- [ ] Uma FB Page real com IG Business vinculado que você administra (pode ser
      página de teste da NC5 ou Vivensi)
- [ ] Navegador em janela anônima, browser em **inglês** (menus, timestamps)
      — muda em Settings do Chrome/Firefox por 30 min pra gravar
- [ ] Vivensi logado com usuário reviewer (`metareview@vivensi.app.br` /
      `password`) OU com teu usuário admin de teste
- [ ] Loom / OBS / QuickTime pronto pra gravar tela + microfone
- [ ] Cursor grande + destaque de clique ativado (Loom faz automático)

---

## Estrutura do vídeo (5 minutos)

| Tempo | Bloco | Permissão |
|-------|-------|-----------|
| 0:00 – 0:30 | Intro + arquitetura server-to-server | Todas |
| 0:30 – 1:15 | Conectar Facebook (OAuth + `pages_show_list`) | pages_show_list |
| 1:15 – 1:30 | IG Business auto-detectado | instagram_basic |
| 1:30 – 2:00 | Criar post agendado | (contexto) |
| 2:00 – 2:45 | Publicar em FB Page | pages_manage_posts |
| 2:45 – 3:00 | Confirmar post live no Facebook | pages_manage_posts |
| 3:00 – 3:30 | Dashboard de engagement | pages_read_engagement |
| 3:30 – 4:00 | Criar post pra Instagram | (contexto) |
| 4:00 – 4:45 | Publicar em IG + confirmar live | instagram_content_publish |
| 4:45 – 5:00 | Fluxo WhatsApp + business_management (server-side) | business_management |

---

## Script detalhado

### 0:00 – 0:30 — Intro (o que dizer + o que mostrar)

**Tela:** Homepage do Vivensi (`https://vivensi.app.br`)

**Áudio (PT):**
> "Olá, equipe de análise da Meta. Meu nome é Jose Lopes, sou o desenvolvedor
> responsável pelo Vivensi, uma plataforma SaaS brasileira para pequenas
> organizações, ONGs e autônomos gerenciarem sua comunicação. Vou demonstrar
> como usamos as seis permissões solicitadas."

**Legenda EN:**
> Hi Meta review team. I'm Jose Lopes, the developer behind Vivensi, a
> Brazilian SaaS platform for small organizations, NGOs and freelancers to
> manage their communication. I'll demonstrate how we use the six
> requested permissions.

**Áudio (PT):**
> "Importante: o Vivensi é uma arquitetura servidor a servidor. O
> FB.login é usado apenas para obter um código de autorização. O backend
> troca esse código por um System User Access Token e faz todas as
> chamadas de API a partir do servidor."

**Legenda EN:**
> Important: Vivensi is a server-to-server architecture. FB.login is
> used only to obtain an authorization code. The backend exchanges it
> for a System User Access Token and makes all API calls server-side.

**Ação na tela:** cursor pousa em `/social/accounts` no menu.

---

### 0:30 – 1:15 — OAuth + pages_show_list

**Tela:** `/social/accounts` (mostra "Nenhuma conta conectada" e botão
"Conectar com Facebook")

**Áudio (PT):**
> "Este é o painel onde meus clientes conectam suas contas do Facebook.
> Vou clicar em Conectar com Facebook."

**Legenda EN:**
> This is where our clients connect their Facebook accounts. I'll click
> Connect with Facebook.

**Ação:** clica no botão.

**Tela:** dialog de OAuth da Meta abre. **CRUCIAL — deixar a tela
enquadrada pra Meta ver a lista de escopos que o app pede.** Se o
navegador está em inglês, os escopos aparecem em inglês.

**Áudio (PT):**
> "A Meta mostra os escopos que o Vivensi solicita: pages_show_list,
> pages_read_engagement, pages_manage_posts, instagram_basic,
> instagram_content_publish e business_management. Clico em Continuar."

**Legenda EN:**
> Meta shows the scopes Vivensi requests: pages_show_list,
> pages_read_engagement, pages_manage_posts, instagram_basic,
> instagram_content_publish and business_management. I click Continue.

**Ação:** aceitar. Meta redireciona pra `/social/facebook/callback`,
código é enviado ao backend, backend chama `GET /me/accounts` (essa
chamada NÃO é visível pro usuário — mencionar em áudio).

**Áudio (PT):**
> "Neste momento, o backend do Vivensi executa três chamadas
> server-side: uma para trocar o código por um long-lived token, outra
> para listar as páginas usando pages_show_list, e outra para detectar
> o Instagram Business vinculado."

**Legenda EN:**
> The backend now runs three server-side calls: exchange the code for
> a long-lived token, list Pages using pages_show_list, and detect the
> linked Instagram Business account.

**Tela:** volta em `/social/accounts` com a Page conectada visível
(nome, foto, tag "Facebook + Instagram").

---

### 1:15 – 1:30 — instagram_basic (Instagram Business auto-detectado)

**Áudio (PT):**
> "Perfeito. O Vivensi identificou minha Página do Facebook e a conta
> do Instagram Business vinculada — usando a permissão instagram_basic
> para ler apenas o ID, username e foto de perfil."

**Legenda EN:**
> Great. Vivensi detected my Facebook Page and the linked Instagram
> Business account, using instagram_basic to read only the ID, username
> and profile picture.

**Ação:** hover no card mostrando "Instagram: @conta_teste"

---

### 1:30 – 2:00 — Criar post agendado

**Tela:** menu → Redes Sociais → Novo Post (`/social/posts/create`)

**Áudio (PT):**
> "Agora vou criar um post para publicar imediatamente, primeiro só no
> Facebook."

**Legenda EN:**
> Now I'll create a post to publish immediately, first only on Facebook.

**Ação:** preencher form:
- Caption: "Publicação de teste — App Review Meta 2026-07-28"
- Upload imagem (qualquer PNG pequeno)
- Plataforma: **Facebook**
- Data/hora: **agora**

---

### 2:00 – 2:45 — pages_manage_posts (publicar em FB)

**Áudio (PT):**
> "Clico em Publicar Agora. O worker do Vivensi vai processar
> imediatamente."

**Legenda EN:**
> I click Publish Now. Vivensi's worker will process it immediately.

**Ação:** submit. Redireciona para lista com o post em status "Publicando..."
→ recarregar até status "Publicado" (10-30s).

**Áudio (PT):**
> "Nos bastidores, o backend chama POST barra ID-da-página barra photos
> com a imagem e a legenda, usando o token de página que obtivemos com
> pages_show_list. Essa chamada requer pages_manage_posts."

**Legenda EN:**
> Behind the scenes, the backend calls POST /{page_id}/photos with the
> image and caption, using the Page token from pages_show_list. That
> call requires pages_manage_posts.

---

### 2:45 – 3:00 — Confirmar post live

**Ação:** clicar no link "Ver no Facebook" que abre a postagem real na
Page do Facebook.

**Áudio (PT):**
> "O post está publicado ao vivo na página do Facebook."

**Legenda EN:**
> The post is live on the Facebook Page.

---

### 3:00 – 3:30 — pages_read_engagement

**Tela:** voltar em `/social/posts` (lista de agendados/publicados),
clicar no post pra ver detalhes com métricas.

**Áudio (PT):**
> "Neste dashboard, o Vivensi mostra o engajamento do post — curtidas,
> comentários, alcance. O backend usa pages_read_engagement para
> consultar essas métricas via GET barra ID-do-post com fields de
> summary."

**Legenda EN:**
> This dashboard shows the post's engagement — likes, comments, reach.
> The backend uses pages_read_engagement to query these via
> GET /{post_id} with summary fields.

**Ação:** hover nas métricas (mesmo que estejam zeradas — dá pra
mencionar "acabamos de publicar, o Facebook está processando").

---

### 3:30 – 4:00 — Criar post pro Instagram

**Tela:** Novo Post novamente.

**Áudio (PT):**
> "Agora vou fazer o mesmo pro Instagram Business."

**Legenda EN:**
> Now the same for Instagram Business.

**Ação:** preencher:
- Caption: "IG post — App Review Meta"
- Upload imagem quadrada
- Plataforma: **Instagram**
- Data: **agora**

---

### 4:00 – 4:45 — instagram_content_publish

**Ação:** submit. Aguarda status "Publicado".

**Áudio (PT):**
> "O Vivensi executa dois passos server-side para publicar no Instagram:
> primeiro POST barra ID-do-IG barra media com a URL da imagem, que
> retorna um container ID. Segundo POST barra ID-do-IG barra media
> underscore publish com esse container. Ambas requerem
> instagram_content_publish."

**Legenda EN:**
> Vivensi runs two server-side steps to publish on Instagram: first
> POST /{ig_id}/media with the image URL returns a container ID.
> Second POST /{ig_id}/media_publish with that container. Both require
> instagram_content_publish.

**Ação:** abrir o Instagram real da conta business no celular ou
outra aba, mostrar o post publicado.

---

### 4:45 – 5:00 — business_management

**Tela:** WhatsApp → Conectar (`/whatsapp/cloud/connect`) — só pra
mostrar o botão, NÃO clicar (Dev mode ainda vai bloquear).

**Áudio (PT):**
> "Por último, o business_management. Esta permissão é usada
> exclusivamente dentro do fluxo Embedded Signup do WhatsApp Business
> Cloud API. O front-end abre o FB.login para o cliente autorizar. O
> backend troca o código por um System User Access Token e usa esse
> token para acessar apenas os assets WhatsApp Business do cliente —
> waba_id e phone_number_id — que persistimos criptografados. Nunca
> acessamos ad accounts, catalogs ou outros assets. Como a arquitetura
> é servidor a servidor, o uso real da permissão acontece após o
> retorno do FB.login, no backend. O código está deployado em produção
> e será executado assim que o app estiver Live."

**Legenda EN:**
> Finally, business_management. This permission is used exclusively
> inside the WhatsApp Business Cloud API Embedded Signup flow. The
> front-end opens FB.login for the client to authorize. The backend
> exchanges the code for a System User Access Token and uses it to
> access only the client's WhatsApp Business assets — waba_id and
> phone_number_id — persisted encrypted. We never access ad accounts,
> catalogs or other assets. Because the architecture is server-to-server,
> the actual use of this permission happens after FB.login returns, on
> the backend. The code is deployed in production and will execute as
> soon as the app is Live.

**Tela final:** fecha com logo do Vivensi ou tela principal.

**Áudio (PT):**
> "Obrigado pela análise. Se precisarem de qualquer clarificação,
> contato at vivensi ponto app ponto br."

**Legenda EN:**
> Thank you for the review. For any clarification, contato@vivensi.app.br.

---

## Dicas de gravação

- **Loom** — grátis pra vídeos até 25 min, gera link direto compartilhável
  na hora, transcreve em EN automaticamente (revise depois)
- **Descript** — melhor legenda automática se você quer queimar a legenda
  no vídeo
- **CapCut** — grátis, permite queimar legenda em inglês com preview
- Grava em janela **1080p ou 1440p** — Meta rejeita vídeo com resolução
  baixa demais ("legibility")
- **Não usar áudio de fundo/música** — dificulta o entendimento
- Fala **devagar** — 130-140 palavras por minuto. Não corre.
- Se errar, corta e regrava só o bloco — não regrava tudo

## O que NÃO fazer

- ❌ Mostrar dados reais de clientes (usa conta de teste)
- ❌ Deixar informações sensíveis visíveis (tokens, senhas)
- ❌ Cortar bruscamente entre telas — deixa 1s de respiração
- ❌ Gravar sem áudio (Meta rejeita explicitamente)
- ❌ Legenda genérica tipo "user clicks button" — legenda tem que **explicar**
  o que está acontecendo tecnicamente

## Upload

Meta aceita:
- Link YouTube público **ou não listado**
- Link Loom público
- Link Google Drive com "Qualquer pessoa com o link pode ver"
- Upload direto de MP4 até 2 GB

Recomendação: **YouTube não listado**. Fica arquivado, você pode voltar
depois pra futuras submissões, não expira.
