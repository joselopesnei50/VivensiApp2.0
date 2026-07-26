<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extrai e-mails do site público de um prospect (home + página de contato).
 * Usado como enriquecimento passivo — quando o scraping falha, o campo fica
 * vazio e o usuário pode preencher manualmente via UI.
 *
 * Faz busca de mailto: e regex simples de e-mail apenas em páginas do próprio
 * domínio (mesma host). Timeout curto e best-effort — nunca lança exceção.
 */
class ProspectEmailScraperService
{
    /** Domínios cujos e-mails são "de plataforma" e devem ser descartados. */
    private const NOISE_DOMAINS = [
        'sentry.io', 'sentry-next.io',
        'wixpress.com', 'wix.com',
        'squarespace.com',
        'godaddy.com',
        'godaddysites.com',
        'example.com', 'example.org',
        'domain.com', 'yourdomain.com',
        'test.com',
        'no-reply.com', 'noreply.com',
        'sentry.wixpress.com',
    ];

    /** Prefixos genéricos de plataforma que ignoramos (não são reais). */
    private const NOISE_LOCAL_PARTS = [
        'no-reply', 'noreply', 'donotreply', 'do-not-reply',
        'wordpress', 'admin@localhost',
    ];

    /** Extensões de arquivo suspeitas em URLs (falsos positivos como fonts). */
    private const NOISE_EXTENSIONS = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.ico'];

    /**
     * Retorna a lista de e-mails únicos encontrados no site do prospect.
     * Ordem: e-mails de "contato" primeiro (contato@, atendimento@, comercial@),
     * depois os demais. Máx 5.
     */
    public function scrape(string $websiteUrl): array
    {
        $normalized = $this->normalizeUrl($websiteUrl);
        if (!$normalized) {
            return [];
        }

        $host = parse_url($normalized, PHP_URL_HOST);
        if (!$host) {
            return [];
        }

        $emails = [];

        // Página inicial
        $html = $this->fetch($normalized);
        if ($html) {
            $emails = array_merge($emails, $this->extractEmails($html));
        }

        // Página /contato ou /contact (heurística, best-effort)
        foreach (['/contato', '/contact', '/fale-conosco', '/sobre'] as $suffix) {
            $contactUrl = rtrim($normalized, '/') . $suffix;
            $htmlContact = $this->fetch($contactUrl);
            if ($htmlContact) {
                $emails = array_merge($emails, $this->extractEmails($htmlContact));
                if (count($emails) >= 8) break; // já tem material o suficiente
            }
        }

        // Dedup + filtro + ranking
        $emails = array_map('strtolower', $emails);
        $emails = array_unique($emails);
        $emails = array_filter($emails, fn ($e) => $this->isRealisticEmail($e, $host));
        $emails = $this->rankEmails(array_values($emails));

        return array_slice($emails, 0, 5);
    }

    /** Fetch HTTP com timeout curto — retorna string ou null. Nunca lança. */
    private function fetch(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; VivensiProspectBot/1.0; +https://vivensi.app.br)',
                'Accept'     => 'text/html,application/xhtml+xml',
            ])
            ->timeout(8)
            ->connectTimeout(4)
            ->withOptions(['allow_redirects' => ['max' => 3]])
            ->get($url);

            if ($response->successful()) {
                return substr((string) $response->body(), 0, 300_000); // cap 300KB
            }
        } catch (\Throwable $e) {
            // Silencioso: sites fora do ar, SSL inválido, DNS ruim — só ignora.
        }

        return null;
    }

    /** Extrai e-mails de mailto: e regex simples no HTML. */
    private function extractEmails(string $html): array
    {
        $emails = [];

        // mailto: links
        if (preg_match_all('/mailto:([^"\'?\s<>]+)/i', $html, $m)) {
            foreach ($m[1] as $addr) {
                // remove ?subject=... se houver
                $addr = explode('?', $addr)[0];
                if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $addr;
                }
            }
        }

        // Regex plana (última linha de defesa)
        if (preg_match_all(
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            $html,
            $m2
        )) {
            foreach ($m2[0] as $addr) {
                if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $addr;
                }
            }
        }

        return $emails;
    }

    /** Descarta emails de plataforma, obfuscados ou "assets". */
    private function isRealisticEmail(string $email, string $host): bool
    {
        // Extensões de imagem coladas ao regex
        foreach (self::NOISE_EXTENSIONS as $ext) {
            if (str_contains($email, $ext)) return false;
        }

        [$local, $domain] = explode('@', $email, 2);

        // Prefixos genéricos
        foreach (self::NOISE_LOCAL_PARTS as $bad) {
            if (str_starts_with($local, $bad)) return false;
        }

        // Domínios de plataforma
        foreach (self::NOISE_DOMAINS as $noisy) {
            if (str_contains($domain, $noisy)) return false;
        }

        // Comprimento mínimo razoável
        if (strlen($email) < 6 || strlen($email) > 120) return false;

        return true;
    }

    /**
     * Prioriza e-mails de negócio (contato@, comercial@, vendas@) sobre
     * pessoais aleatórios encontrados na página.
     */
    private function rankEmails(array $emails): array
    {
        $priorityPrefixes = ['contato', 'atendimento', 'comercial', 'vendas', 'sac', 'faleconosco', 'ola', 'oi'];

        usort($emails, function ($a, $b) use ($priorityPrefixes) {
            $aLocal = strtolower(explode('@', $a)[0]);
            $bLocal = strtolower(explode('@', $b)[0]);

            $aPriority = 999;
            $bPriority = 999;
            foreach ($priorityPrefixes as $i => $prefix) {
                if ($aPriority === 999 && str_starts_with($aLocal, $prefix)) $aPriority = $i;
                if ($bPriority === 999 && str_starts_with($bLocal, $prefix)) $bPriority = $i;
            }

            return $aPriority <=> $bPriority;
        });

        return $emails;
    }

    /** Adiciona esquema se o site vier sem, garante http/https. */
    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') return null;

        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ?: null;
    }
}
