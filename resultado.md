# Resultado — Go-Live Vivensi (2026-06-03)

## Bloqueadores fechados

- **B1 — Credenciais hardcoded**: `server_setup.sh` e `evolution-docker-compose.yml` passaram a ler de env; senha do MySQL rotacionada via `infra/rotate-db-password.sh`; `EVOLUTION_GLOBAL_KEY` rotacionada nos dois lados (Lightsail e VPS Vivensi).
- **B2 — Migrations no VPS**: zero pendentes.
- **B3 — `.env` de produção**: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=redis`, `APP_URL=https://vivensi.app.br`, `SENTRY_LARAVEL_DSN` configurado.

## Smoke tests pós-rotação

- Evolution autentica com chave nova: `HTTP 200`
- Evolution rejeita chave inválida: `HTTP 401`
- App Vivensi → Evolution com chave nova: `HTTP 200`

---

## Logs de execução (credenciais mascaradas)

ubuntu@ip-172-26-10-197:/opt/evolution-api$ sudo sed -i "s|^AUTHENTICATION_API_KEY=.*|AUTHENTICATION_API_KEY=$NEW_KEY|"

  .env

sed: no input files
Command '.env' not found, did you mean:
  command 'tenv' from snap tenv (4.12.2)
  command 'genv' from snap genv (2.1.2)
  command 'env' from deb coreutils (8.32-4.1ubuntu1.3)
See 'snap info <snapname>' for additional versions.
ubuntu@ip-172-26-10-197:/opt/evolution-api$ sudo grep ^AUTHENTICATION_API_KEY .env
AUTHENTICATION_API_KEY=[REDACTED-REVOKED-KEY]
ubuntu@ip-172-26-10-197:/opt/evolution-api$ pm2 restart evolution-api
Use --update-env to update environment variables
[PM2] Applying action restartProcessId on app [evolution-api](ids: [ 0 ])
[PM2] [evolution-api](0) ✓
┌────┬────────────────────┬──────────┬──────┬───────────┬──────────┬──────────┐
│ id │ name               │ mode     │ ↺    │ status    │ cpu      │ memory   │
├────┼────────────────────┼──────────┼──────┼───────────┼──────────┼──────────┤
│ 0  │ evolution-api      │ fork     │ 8    │ online    │ 0%       │ 20.4mb   │
└────┴────────────────────┴──────────┴──────┴───────────┴──────────┴──────────┘
ubuntu@ip-172-26-10-197:/opt/evolution-api$ pm2 logs evolution-api --lines 30 --nostream
[TAILING] Tailing last 30 lines for [evolution-api] process (change the value with --lines option)home/ubuntu/.pm2/logs/evolution-api-error.log last 30 lines:
0|evolutio |     at Object.130365897347210_1.6 [as awaitable] (/opt/evolution-api/node_modules/libsignal/src/session_cipher.js:171:39)
0|evolutio |     at processTicksAndRejections (node:internal/process/task_queues:95:5)
0|evolutio |     at _asyncQueueExecutor (/opt/evolution-api/node_modules/libsignal/src/queue_job.js:20:29)
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Error: Unsupported state or unable to authenticate data
0|evolutio |     at Decipheriv.final (node:internal/crypto/cipher:193:29)
0|evolutio |     at aesDecryptGCM (file:///opt/evolution-api/node_modules/baileys/src/Utils/crypto.ts:71:55)
0|evolutio |     at decrypt (file:///opt/evolution-api/node_modules/baileys/src/Utils/noise-handler.ts:49:18)
0|evolutio |     at Object.decodeFrame (file:///opt/evolution-api/node_modules/baileys/src/Utils/noise-handler.ts:181:21)
0|evolutio |     at WebSocketClient.onMessageReceived (file:///opt/evolution-api/node_modules/baileys/src/Socket/socket.ts:534:9)
0|evolutio |     at WebSocketClient.emit (node:events:524:28)0|evolutio |     at WebSocket.<anonymous> (file:///opt/evolution-api/node_modules/baileys/src/Socket/Client/websocket.ts:39:52)
0|evolutio |     at WebSocket.emit (node:events:524:28)
0|evolutio |     at Receiver.receiverOnMessage (/opt/evolution-api/node_modules/ws/lib/websocket.js:1220:20)
0|evolutio |     at Receiver.emit (node:events:524:28)
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle
0|evolutio | Closing open session in favor of incoming prekey bundle

/home/ubuntu/.pm2/logs/evolution-api-out.log last 30 lines:
0|evolutio | 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:22:24     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"80999576785144@lid","id":"3EB0B2E981B3AA40276AC0","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:22:24     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"80999576785144@lid","id":"3EB033457348C8CC6ECE3E","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:22:24     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"80999576785144@lid","id":"3EB004260CF49C8EB4EF25","fromMe":true} 0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:22:24     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"80999576785144@lid","id":"3EB051373AFE52374D0598","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:22:24     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"80999576785144@lid","id":"3EB0DC36985667C35A274E","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:25:08     ERROR   [ChannelStartupService]  [string]  Failed to resolve participant data for GROUP_PARTICIPANTS_UPDATE webhook: o.split is not a function | Group: 120363410656589891@g.us | Participants: 1 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:25:08     ERROR   [ChannelStartupService]  [string]  Failed to resolve participant data for GROUP_PARTICIPANTS_UPDATE webhook: o.split is not a function | Group: 120363425933895218@g.us | Participants: 1 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:46:47     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"23837420835031@lid","id":"AC5AC48EB649D734451D4299CB4491A7","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:46:47     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"23837420835031@lid","id":"AC528563940E9ADEFBF3F572A993EC33","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sat May 30 2026 22:46:47     WARN   [ChannelStartupService]  [string]  Original message not found for update. Skipping. Key: {"remoteJid":"23837420835031@lid","id":"3EB067340E77A032C9146D","fromMe":true} 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sun May 31 2026 01:26:22     ERROR   [ChannelStartupService]  [string]  Failed to resolve participant data for GROUP_PARTICIPANTS_UPDATE webhook: o.split is not a function | Group: 120363424489762775@g.us | Participants: 1 
0|evolutio | [Evolution API]  [vivensi_t19_dj7zWz]  v2.3.6  187717   -  Sun May 31 2026 01:26:22     ERROR   [ChannelStartupService]  [string]  Failed to resolve participant data for GROUP_PARTICIPANTS_UPDATE webhook: o.split is not a function | Group: 120363425933895218@g.us | Participants: 1 
0|evolutio | Cache request for group: 120363410656589891@g.us
0|evolutio | Cache request for group: 120363410656589891@g.us
0|evolutio | {"level":50,"time":1780217143593,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}
0|evolutio | [Evolution API]    v2.3.6  187717   -  Sun May 31 2026 08:45:48     WARN   [WAMonitoringService]  [string]  Instance "vivensi_t19_dj7zWz" - LOGOUT 
0|evolutio | {"level":50,"time":1780349988041,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}
0|evolutio | {"level":50,"time":1780350738494,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}
0|evolutio | {"level":50,"time":1780403501539,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}
0|evolutio | {"level":50,"time":1780438635986,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}
0|evolutio | {"level":50,"time":1780442626511,"pid":187717,"hostname":"ip-172-26-10-197","node":{"tag":"stream:error","attrs":{"code":"503"}},"msg":"stream errored out"}

ubuntu@ip-172-26-10-197:/opt/evolution-api$ 
