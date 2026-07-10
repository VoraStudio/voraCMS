<?php

/* ══════════════════════════════════════════════════════════════
   DomainService — VoraCMS
   ══════════════════════════════════════════════════════════════
   Normalització de dominis per comparar de manera consistent.

   - Treu http://, https://
   - Treu www. (www.example.com → example.com)
   - Treu trailing slash
   - Treu path/query (si algú passa URL sencera)
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

readonly class DomainService
{
    /**
     * Normalitza un domini per a comparació.
     *
     * Exemples:
     *   "https://www.example.com" → "example.com"
     *   "http://example.com/path" → "example.com"
     *   "www.example.com"         → "example.com"
     *   "example.com"             → "example.com"
     */
    public function normalize(string $input): string
    {
        $domain = trim($input);

        // Treure protocol
        $domain = preg_replace('#^https?://#i', '', $domain);

        // Treure www.
        $domain = preg_replace('#^www\.#i', '', $domain);

        // parse_url per treure path, query, port si la van colar
        $host = parse_url($domain, PHP_URL_HOST);
        if ($host !== false && $host !== null) {
            $domain = $host;
        }

        // Treure trailing slash per si de cas
        return rtrim($domain, '/');
    }

    /**
     * Comprova si dos dominis coincideixen després de normalitzar.
     */
    public function matches(string $input, string $allowed): bool
    {
        return $this->normalize($input) === $this->normalize($allowed);
    }
}
