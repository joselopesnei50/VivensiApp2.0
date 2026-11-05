<?php

namespace App\Support;

/**
 * Proteção contra SSRF para URLs informadas por clientes (ex.: webhooks).
 * Só aceita http/https para hosts que resolvem APENAS para IPs públicos —
 * bloqueia loopback, redes privadas, link-local (metadados da nuvem
 * 169.254.169.254), faixas reservadas e nomes internos.
 *
 * check() devolve o IP validado para "fixar" a conexão nele (CURLOPT_RESOLVE),
 * evitando que o DNS mude entre a checagem e o envio (DNS rebinding).
 */
class SafeOutboundUrl
{
    /** Resolve um host em IPs. Substituível nos testes. */
    public static ?\Closure $resolver = null;

    private const BLOCKED_HOSTS = ['localhost', 'localhost.localdomain', 'ip6-localhost', 'metadata', 'metadata.google.internal'];

    /**
     * @return array{ok: bool, reason?: string, host?: string, port?: int, ip?: string}
     */
    public static function check(?string $url): array
    {
        $parts = parse_url((string) $url);
        if (! $parts || empty($parts['host']) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return ['ok' => false, 'reason' => 'Use um endereço http:// ou https:// válido.'];
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return ['ok' => false, 'reason' => 'Endereço com usuário/senha embutidos não é aceito.'];
        }

        $host = strtolower(trim($parts['host'], '[]'));
        $port = (int) ($parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80));

        if (in_array($host, self::BLOCKED_HOSTS, true)
            || preg_match('~\.(local|localhost|internal|intranet|lan|home|corp)$~', $host)) {
            return ['ok' => false, 'reason' => 'Endereços internos não são permitidos.'];
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if (! $ips) {
            return ['ok' => false, 'reason' => 'Não foi possível encontrar esse domínio.'];
        }
        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return ['ok' => false, 'reason' => 'Endereços internos ou de rede privada não são permitidos.'];
            }
        }

        return ['ok' => true, 'host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    public static function isPublicIp(string $ip): bool
    {
        // IPv4 mapeado em IPv6 (::ffff:127.0.0.1)
        if (preg_match('~^::ffff:(\d+\.\d+\.\d+\.\d+)$~i', $ip, $m)) {
            $ip = $m[1];
        }
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // Faixas que o filtro do PHP não cobre em todas as versões
        foreach (['100.64.0.0/10', '198.18.0.0/15', '192.0.0.0/24', '0.0.0.0/8', '169.254.0.0/16', '127.0.0.0/8'] as $cidr) {
            if (str_contains($ip, '.') && self::inCidr($ip, $cidr)) {
                return false;
            }
        }
        return true;
    }

    /** @return string[] */
    private static function resolve(string $host): array
    {
        if (self::$resolver) {
            return (self::$resolver)($host);
        }
        $ips = [];
        foreach ([DNS_A, DNS_AAAA] as $type) {
            $records = @dns_get_record($host, $type) ?: [];
            foreach ($records as $r) {
                if (! empty($r['ip'])) $ips[] = $r['ip'];
                if (! empty($r['ipv6'])) $ips[] = $r['ipv6'];
            }
        }
        if (! $ips) {
            $ips = @gethostbynamel($host) ?: [];
        }
        return array_values(array_unique($ips));
    }

    private static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $mask = -1 << (32 - (int) $bits);
        return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
    }
}
