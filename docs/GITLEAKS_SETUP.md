# Gitleaks — Prevenção de vazamento de segredos

Ferramenta que escaneia arquivos e histórico Git em busca de padrões que se assemelham a credenciais (API keys, senhas, tokens, chaves privadas). Não roda em produção — é 100% dev/CI.

## Arquitetura no Vivensi

| Camada | Onde | Quando roda | Efeito |
|---|---|---|---|
| **CI (obrigatório)** | Job `gitleaks` em `.github/workflows/laravel.yml` | Todo push/PR em `main` | Falha o pipeline se detectar segredo → PR não mergea |
| **Pre-commit (opcional)** | `.githooks/pre-commit` | Todo `git commit` local | Bloqueia o commit antes de sair da sua máquina |
| **Config compartilhada** | `.gitleaks.toml` | Ambas as camadas | Allowlist para credenciais já rotacionadas e paths de teste |

## Ativar hook local (recomendado)

### 1. Instalar gitleaks

- **macOS:** `brew install gitleaks`
- **Windows:** `winget install gitleaks.gitleaks` (ou `scoop install gitleaks`)
- **Linux:** ver https://github.com/gitleaks/gitleaks#installing
- **Docker:** `docker run --rm -v $(pwd):/repo zricethezav/gitleaks:latest ...`

### 2. Apontar Git para o hook do repo

```bash
git config core.hooksPath .githooks
```

Só precisa fazer uma vez por clone. A partir daí, cada `git commit` roda o scan nos arquivos staged.

## Se o hook bloquear um commit legítimo (falso positivo)

Edite `.gitleaks.toml` e adicione o valor ou path ao bloco `[allowlist]`:

```toml
[allowlist]
regexes = [
    '''seu-valor-que-parece-segredo-mas-nao-eh''',
]
paths = [
    '''caminho/do/arquivo/de/fixture\.php$''',
]
```

Commite a config junto. **Nunca use `git commit --no-verify`** para bypass — o objetivo do hook é justamente impedir isso.

## Rodar scan manualmente

```bash
# Scan de arquivos staged (mesma coisa que o pre-commit roda)
gitleaks protect --staged --config .gitleaks.toml --verbose

# Scan do working tree atual (arquivos não commitados)
gitleaks protect --config .gitleaks.toml --verbose

# Scan de TODO o histórico Git (todos os commits, todas as branches)
gitleaks detect --config .gitleaks.toml --verbose
```

## Credenciais historicamente vazadas (já rotacionadas)

Estas strings aparecem no histórico Git do repo, mas foram rotacionadas em produção e confirmadamente inertes. Estão no allowlist para não gerarem ruído em scans de histórico:

| Variável | Valor antigo (inerte) | Data rotação |
|---|---|---|
| `EVOLUTION_GLOBAL_KEY` | `e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e` | Antes de 2026-07 |
| `DB_PASSWORD` | `Viv3nsi@2026` | Antes de 2026-07 |
| `AUTHENTICATION_API_KEY` | `SenhaForteVivensi2026@!` | Antes de 2026-07 |

Verificação de inertização: `infra/verify-leaked-credentials.sh` (script read-only que testa contra MySQL e Evolution API).
