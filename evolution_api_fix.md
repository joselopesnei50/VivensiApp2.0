Claro! Abaixo está um resumo organizado para você copiar e colar diretamente na sua IDE (como comentários, um arquivo `.md` ou na conversa com o assistente da IDE). Ele contém **apenas as ações práticas** – comandos, configurações e trechos de código – para resolver o problema da geração do QR Code na Evolution API.

---

```markdown
# 🔧 Solução: Evolution API não gera QR Code (servidor separado)

## 1. Diagnóstico inicial
```bash
# No servidor onde roda a Evolution API
docker logs evolution-api -f --tail 100
```
Se vir loop infinito ou nenhum QR Code, aplicar passos abaixo.

## 2. Atualizar imagem Docker da Evolution API (a partir do código fonte)
```bash
cd ~
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
docker build -t evolution-api:latest .
```

Atualize seu `docker-compose.yml`:
```yaml
evolution-api:
  image: evolution-api:latest   # ← usar imagem construída
  # ... outras configurações
```

Reinicie:
```bash
docker-compose down
docker-compose up -d
```

## 3. Configurar variáveis de ambiente (.env da Evolution API)
```ini
CONFIG_SESSION_PHONE_VERSION=2.3000.1023204200
SERVER_URL=https://evolution-api.seuservidor.com:8080   # URL pública da API
CACHE_LOCAL_ENABLED=false
LOG_LEVEL=DEBUG
CORS_ORIGIN=https://seu-sistema.com.br                 # URL do seu sistema
```

Após alterar, reinicie:
```bash
docker-compose restart evolution-api
```

## 4. Testar comunicação entre os servidores
Do servidor principal (seu sistema) para a Evolution API:
```bash
curl -I https://evolution-api.seuservidor.com:8080/status
```
Do servidor da Evolution API para o seu sistema:
```bash
curl -I https://seu-sistema.com.br
```
Se falhar, libere portas no firewall/security group.

## 5. Criar instância com QR Code (via API)
```json
{
  "instanceName": "minha_instancia",
  "qrcode": true,
  "integration": "WHATSAPP-BAILEYS"
}
```
**Atenção:** `integration` deve ser `"WHATSAPP-BAILEYS"`, não `"EVOLUTION"`.

## 6. Alternativa: usar Pairing Code (código de 8 dígitos, sem QR Code)
```json
{
  "instanceName": "minha_instancia",
  "number": "5511999999999",
  "integration": "WHATSAPP-BAILEYS"
}
```
A API retornará um código de 8 dígitos. No WhatsApp, vá em **Configurações > Dispositivos vinculados > Vincular um dispositivo** e digite o código.

## 7. Verificar se a instância está pronta
```bash
curl https://evolution-api.seuservidor.com:8080/instance/fetchInstances
```

## 8. (Opcional) Teste de criação direta com curl
```bash
curl -X POST https://evolution-api.seuservidor.com:8080/instance/create \
  -H "Content-Type: application/json" \
  -d '{
    "instanceName": "teste_qrcode",
    "qrcode": true,
    "integration": "WHATSAPP-BAILEYS"
  }'
```

---
**Próximo passo:** após criar a instância, acesse o endpoint de QR Code:
```
GET https://evolution-api.seuservidor.com:8080/instance/qrcode/teste_qrcode?format=image
```
(ou use o retorno da criação para obter o QR em base64)
```

---

Aqui está o conteúdo completo e organizado para você colar no arquivo `evolution_api_fix.md` (pode criar ou sobrescrever). Ele já incorpora todas as informações adicionais que discutimos.

```markdown
# 🔧 Solução: Evolution API não gera QR Code (arquitetura com servidor separado)

Este guia resolve o problema de **QR Code não gerado** ao conectar uma instância do WhatsApp via Evolution API, especialmente quando a API roda em servidor diferente do sistema principal (ex.: `vivensi.app.br`).

---

## ✅ 1. Diagnóstico inicial

```bash
# No servidor onde a Evolution API está rodando
docker logs evolution-api -f --tail 100
```

**O que procurar:**
- Loop infinito sem QR Code → versão desatualizada (mais comum)
- Nenhuma mensagem nova → instância pode estar travada

---

## 🐳 2. Atualizar imagem Docker (a partir do código fonte)

> **Solução mais eficaz** – resolve o bug da versão 2.2.3 e anteriores.

```bash
cd ~
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
docker build -t evolution-api:latest .
```

Altere o `docker-compose.yml` para usar a nova imagem:

```yaml
evolution-api:
  image: evolution-api:latest   # ← use a imagem construída
  # ... outras configurações
```

Reinicie os containers:

```bash
docker-compose down
docker-compose up -d
```

---

## ⚙️ 3. Configurar variáveis de ambiente (arquivo `.env` da Evolution API)

Adicione ou modifique as seguintes variáveis:

```ini
# Versão estável do WhatsApp Web
CONFIG_SESSION_PHONE_VERSION=2.3000.1023204200

# URL pública da sua Evolution API (acessível pelo sistema principal)
SERVER_URL=https://evolution-api.seuservidor.com:8080

# Cache local – desabilite para evitar interferências
CACHE_LOCAL_ENABLED=false

# Logs detalhados
LOG_LEVEL=DEBUG

# CORS – libere acesso do seu sistema principal
CORS_ORIGIN=https://seu-sistema.com.br
```

Após alterar, reinicie a API:

```bash
docker-compose restart evolution-api
```

---

## 🌐 4. Testar comunicação entre os servidores

### Do servidor principal (seu sistema) → Evolution API
```bash
curl -v https://evolution-api.seuservidor.com:8080/status
```

### Da Evolution API → seu sistema principal
```bash
curl -v https://seu-sistema.com.br
```

Se falhar, verifique:
- Firewalls e Security Groups (AWS, etc.)
- Proxy reverso (Nginx/Apache) – certifique-se de que o CORS está configurado

---

## 🧹 5. Limpar cache e sessões antigas (importante após tentativas falhas)

```bash
# Remove todas as instâncias salvas
docker exec -it evolution-api rm -rf /evolution/instances

# Reinicia o container
docker restart evolution-api
```

---

## 🔌 6. Criar instância com QR Code (via API)

**Endpoint:** `POST /instance/create`

```json
{
  "instanceName": "minha_instancia",
  "qrcode": true,
  "integration": "WHATSAPP-BAILEYS"
}
```

> ⚠️ **Atenção:** `integration` deve ser `"WHATSAPP-BAILEYS"`, **não** `"EVOLUTION"`.

### Exemplo com `curl` (teste direto na API)
```bash
curl -X POST https://evolution-api.seuservidor.com:8080/instance/create \
  -H "apikey: SUA_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "instanceName": "teste_diagnostico",
    "qrcode": true,
    "integration": "WHATSAPP-BAILEYS"
  }'
```

Se o comando retornar o QR Code (base64 ou URL), o problema está na sua integração. Se falhar, o problema é na instância da Evolution API.

---

## 📱 7. Alternativa: Pairing Code (código de 8 dígitos – sem QR Code)

Use quando o QR Code simplesmente não aparece.

```json
{
  "instanceName": "minha_instancia",
  "number": "5511999999999",
  "integration": "WHATSAPP-BAILEYS"
}
```

A API retornará um código de 8 dígitos. No WhatsApp do celular:

1. Acesse **Configurações → Dispositivos vinculados → Vincular um dispositivo**
2. Digite o código de 8 dígitos
3. Pronto – a instância conecta automaticamente

---

## 🔍 8. Verificar se a instância está pronta

```bash
curl https://evolution-api.seuservidor.com:8080/instance/fetchInstances
```

Para obter o QR Code (se ainda estiver aguardando):

```bash
curl https://evolution-api.seuservidor.com:8080/instance/qrcode/teste_diagnostico?format=image
```

---

## 📋 Checklist final

| Ação | Comando / Configuração |
|------|------------------------|
| Atualizar imagem Docker | `docker build -t evolution-api:latest .` |
| Limpar sessões antigas | `docker exec -it evolution-api rm -rf /evolution/instances` |
| Variáveis essenciais no `.env` | `CONFIG_SESSION_PHONE_VERSION`, `SERVER_URL`, `LOG_LEVEL=DEBUG` |
| Teste de comunicação | `curl -v` entre os servidores |
| Criar instância com `qrcode: true` | `integration: "WHATSAPP-BAILEYS"` |
| Fallback | Usar **Pairing Code** |

---

## 🆘 Próximos passos se o problema persistir

1. Cole os logs da Evolution API (modo `DEBUG`) no assistente da IDE.
2. Execute o `curl` de criação de instância diretamente no servidor da API.
3. Verifique se o servidor da API tem recursos suficientes (CPU/RAM) – falta de recurso pode causar timeout silencioso.

---

**Versão do documento:** 1.0  
**Última atualização:** 2026-04-04
```

