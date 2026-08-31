<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Encapsula shell exec do fluxo de custom domain (dig + certbot + nginx).
 * Metodos separados pra permitir mock em testes de orquestracao.
 *
 * Requer no VPS:
 *   - certbot + python3-certbot-nginx instalados
 *   - dig disponivel (dnsutils)
 *   - Sudoers whitelist pro user do web (www-data) rodar:
 *       * /usr/bin/certbot
 *       * /usr/sbin/nginx -t
 *       * /bin/systemctl reload nginx
 *       * tee em /etc/nginx/sites-{available,enabled}/
 *       * ln e rm em /etc/nginx/sites-enabled/
 */
class LandingDomainProvisioner
{
    /** IP publico estatico do VPS Vivensi. */
    public const VPS_IP = '34.193.132.112';

    /** Email pra Let's Encrypt (avisos de expiracao/renovacao). */
    public const LE_EMAIL = 'contato@vivensi.app.br';

    /** Path do template nginx no repo. */
    public const NGINX_STUB = 'resources/stubs/lp-nginx.conf.stub';

    /**
     * Verifica se o dominio aponta pro IP do VPS via DNS publico (@8.8.8.8).
     * Retorna array [ok => bool, resolved => string, message => string].
     */
    public function validateDns(string $domain): array
    {
        $safeDomain = escapeshellarg($domain);
        $out = trim((string) shell_exec("dig +short {$safeDomain} @8.8.8.8 A 2>&1"));

        if ($out === '') {
            return ['ok' => false, 'resolved' => '', 'message' => "Nenhum registro A encontrado pra {$domain}. Verifique se o DNS foi propagado (pode levar ate 24h)."];
        }

        // dig pode retornar multiplas linhas (multiple A records). Aceita se qualquer bater.
        $lines = array_filter(array_map('trim', explode("\n", $out)));
        foreach ($lines as $ip) {
            if ($ip === self::VPS_IP) {
                return ['ok' => true, 'resolved' => $ip, 'message' => 'DNS ok.'];
            }
        }

        return ['ok' => false, 'resolved' => implode(', ', $lines), 'message' => "DNS aponta pra {$out}, esperado " . self::VPS_IP . '.'];
    }

    /**
     * Roda certbot pra emitir cert Let's Encrypt via plugin nginx.
     * Retorna [ok => bool, message => string].
     */
    public function issueCertificate(string $domain): array
    {
        $safeDomain = escapeshellarg($domain);
        $safeEmail  = escapeshellarg(self::LE_EMAIL);

        $cmd = "sudo certbot certonly --nginx --agree-tos --email {$safeEmail} --non-interactive --domains {$safeDomain} 2>&1";
        exec($cmd, $out, $code);

        $message = implode("\n", $out);
        Log::info("[cd] certbot code={$code}", ['domain' => $domain, 'out' => $message]);

        return ['ok' => $code === 0, 'message' => $message];
    }

    /**
     * Renova todos os certs expirando via `certbot renew` (idempotente).
     */
    public function renewCertificates(): array
    {
        exec('sudo certbot renew --non-interactive 2>&1', $out, $code);
        $message = implode("\n", $out);
        Log::info("[cd] certbot renew code={$code}", ['out' => $message]);

        return ['ok' => $code === 0, 'message' => $message];
    }

    /**
     * Escreve o server block nginx do dominio a partir do stub.
     * NAO faz reload (chame reloadNginx() depois).
     */
    public function writeNginxConfig(int $landingId, string $domain): array
    {
        $stubPath = base_path(self::NGINX_STUB);
        if (!file_exists($stubPath)) {
            return ['ok' => false, 'message' => "Stub nao encontrado em {$stubPath}"];
        }

        $conf = str_replace(
            ['{ID}', '{DOMAIN}', '{GENERATED_AT}'],
            [(string) $landingId, $domain, now()->toIso8601String()],
            file_get_contents($stubPath)
        );

        $availPath  = "/etc/nginx/sites-available/lp-{$landingId}.conf";
        $enablePath = "/etc/nginx/sites-enabled/lp-{$landingId}.conf";

        // tee via sudo pra escapar do permission denied (www-data nao escreve em /etc/nginx)
        $safeConf = escapeshellarg($conf);
        exec("echo {$safeConf} | sudo tee " . escapeshellarg($availPath) . " > /dev/null 2>&1", $_, $teeCode);
        if ($teeCode !== 0) {
            return ['ok' => false, 'message' => "Falha ao escrever {$availPath}"];
        }

        exec('sudo ln -sf ' . escapeshellarg($availPath) . ' ' . escapeshellarg($enablePath), $_, $lnCode);
        if ($lnCode !== 0) {
            return ['ok' => false, 'message' => "Falha ao criar symlink {$enablePath}"];
        }

        return ['ok' => true, 'message' => "Config escrito em {$availPath} e habilitado."];
    }

    /**
     * Remove config nginx do dominio (usado no unprovision).
     */
    public function removeNginxConfig(int $landingId): array
    {
        $availPath  = "/etc/nginx/sites-available/lp-{$landingId}.conf";
        $enablePath = "/etc/nginx/sites-enabled/lp-{$landingId}.conf";

        exec('sudo rm -f ' . escapeshellarg($enablePath) . ' ' . escapeshellarg($availPath), $_, $code);
        return ['ok' => $code === 0, 'message' => "Configs removidos."];
    }

    /**
     * `nginx -t` valida syntax da config atual (agregado do sistema).
     */
    public function testNginxConfig(): array
    {
        exec('sudo nginx -t 2>&1', $out, $code);
        return ['ok' => $code === 0, 'message' => implode("\n", $out)];
    }

    /**
     * `systemctl reload nginx` recarrega sem downtime.
     */
    public function reloadNginx(): array
    {
        exec('sudo systemctl reload nginx 2>&1', $out, $code);
        return ['ok' => $code === 0, 'message' => implode("\n", $out)];
    }

    /**
     * Faz HTTPS GET no dominio e retorna [ok, httpCode, message].
     */
    public function testHttps(string $domain): array
    {
        $safeDomain = escapeshellarg("https://{$domain}");
        $code = trim((string) shell_exec("curl -sS -o /dev/null -w '%{http_code}' -m 10 -L {$safeDomain} 2>&1"));

        // 200/301/302 sao aceitaveis (site vivo). 404 tambem — significa que
        // chegou no Laravel (que retornou 404 pro path). 5xx/000 = falha.
        $ok = in_array($code, ['200', '301', '302', '404'], true);
        return ['ok' => $ok, 'httpCode' => $code, 'message' => "HTTPS retornou {$code}"];
    }
}
