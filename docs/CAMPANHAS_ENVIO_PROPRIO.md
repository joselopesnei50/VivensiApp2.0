# Campanhas com envio próprio (Amazon SES)

Módulo do **super admin** (`/admin/email-campaigns`) para disparar campanhas de
prospecção sem o plano de marketing da Brevo. A Brevo continua sendo o envio
**transacional** (senha, cobrança, notificações) — nada muda nele.

## Como funciona

```
Disparar → OwnCampaignDispatcher
             ├─ filtra bloqueados (bounce, spam, descadastro)
             ├─ grava 1 linha por destinatário (email_campaign_recipients)
             └─ SendOwnCampaignBatchJob (fila "emails")
                   ├─ envia N e-mails (N = ritmo/min), 1 a 1 pelo SES
                   ├─ personaliza, rastreia abertura/clique, descadastro 1 clique
                   ├─ freio automático: pausa se bounce > 5% ou spam > 0,3%
                   └─ re-agenda em 60s até a fila acabar
SES → SNS → POST /api/webhooks/ses-campaigns → bounce/spam bloqueiam o e-mail para sempre
```

- **Ritmo controlado** por campanha (e-mails/min) e **teto diário** global (aquecimento do domínio).
- **Pausar / Retomar** a qualquer momento; retomar não reenvia para quem já recebeu.
- **Reenviar falhas** reprocessa só quem falhou.
- Se o worker cair no meio, o agendador (`emails:unstuck-campaigns`, a cada 15 min) retoma a fila.
  Quem estava *no meio* do lote é marcado como falha, nunca reenviado em duplicidade.
- Personalização no HTML: `{nome}`, `{primeiro_nome}`, `{email}`, `{unsubscribe_url}`.
- Headers `List-Unsubscribe` + `List-Unsubscribe-Post` (exigidos por Gmail/Yahoo para envio em volume).

## Custos de referência

SES cobra por e-mail enviado (ordem de US$ 0,10 por mil). Lightsail não tem
cobrança extra: o servidor só orquestra; quem entrega é o SES. Confira o preço
atual em https://aws.amazon.com/ses/pricing/.

## Passo a passo na AWS (uma vez)

1. **Domínio dedicado à prospecção** — use um subdomínio, ex.: `contato.vivensi.app.br`.
   Prospecção fria gera mais reclamações; separar protege a reputação do domínio
   principal e dos e-mails transacionais.
2. **SES → Identities → Create identity → Domain** (`contato.vivensi.app.br`), com
   Easy DKIM. Publique os 3 CNAMEs de DKIM no DNS. Configure também:
   - SPF: o SES usa MAIL FROM próprio; ative **Custom MAIL FROM** (ex.: `bounce.contato.vivensi.app.br`) e publique MX + TXT `v=spf1 include:amazonses.com ~all`.
   - DMARC: TXT em `_dmarc.contato.vivensi.app.br` → `v=DMARC1; p=none; rua=mailto:dmarc@vivensi.app.br` (endureça para `quarantine` depois).
3. **Sair do sandbox** — SES → Account dashboard → *Request production access*.
   Descreva: prospecção B2B para organizações do terceiro setor, base própria,
   descadastro em 1 clique, bloqueio automático de bounce/reclamação.
   No sandbox só é possível enviar para e-mails verificados.
4. **Configuration set** `vivensi-campanhas` → Event destination → **SNS**, eventos
   *Bounce*, *Complaint*, *Delivery*. Crie o tópico SNS (ex.: `vivensi-ses-campanhas`).
5. **SNS → Subscription** do tópico: protocolo HTTPS, endpoint
   `https://vivensi.app.br/api/webhooks/ses-campaigns`. A confirmação é automática
   (o webhook valida a assinatura da AWS e confirma).
6. **Usuário IAM** só para campanhas, com a política:
   ```json
   { "Version": "2012-10-17", "Statement": [{ "Effect": "Allow",
     "Action": ["ses:SendRawEmail", "ses:SendEmail"], "Resource": "*" }] }
   ```
7. **.env** do servidor (ver `.env.example`, seção "Campanhas com envio próprio").
   Preencha `CAMPAIGN_SES_SNS_TOPIC_ARN` com o ARN do tópico para aceitar só ele.
8. Rodar a migração `2026_11_02_100001_add_own_delivery_to_email_campaigns` e
   `php artisan config:cache`.

## Aquecimento sugerido (domínio novo)

| Semana | Ritmo (`CAMPAIGN_RATE_PER_MINUTE`) | Teto diário (`CAMPAIGN_DAILY_LIMIT`) |
|---|---|---|
| 1 | 10 | 200 |
| 2 | 20 | 500 |
| 3 | 40 | 1.500 |
| 4+ | 60+ | 0 (sem teto) ou conforme cota do SES |

Comece pelos contatos mais quentes (que já conhecem a Vivensi) e acompanhe bounce
e reclamação na página da campanha.

## Boas práticas para prospecção (LGPD)

- Base B2B com e-mail institucional e relação com o terceiro setor (legítimo interesse, art. 7º IX).
- Identifique claramente a Vivensi no remetente e no rodapé; descadastro é automático.
- Limpe a lista antes de enviar (e-mails inválidos geram bounce e pausam a campanha).

## Trocar de provedor

`CAMPAIGN_MAILER=campaign_smtp` + `CAMPAIGN_SMTP_*` usa qualquer SMTP. Nesse caso
bounces e reclamações não chegam pelo webhook (são específicos do SES).

## Arquivos

- `app/Services/CampaignMail/OwnCampaignDispatcher.php` — fila e métricas
- `app/Services/CampaignMail/CampaignRenderer.php` — personalização e rastreamento
- `app/Jobs/SendOwnCampaignBatchJob.php` — envio em lotes, freios
- `app/Http/Controllers/CampaignTrackingController.php` — pixel, clique, descadastro 1 clique
- `app/Http/Controllers/Api/SesCampaignWebhookController.php` — bounces/reclamações (SNS)
- `config/campaign_mail.php`, mailers `campaign_ses` / `campaign_smtp` em `config/mail.php`
