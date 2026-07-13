<?php

/* ══════════════════════════════════════════════════════════════
   JwtDomainValidator — VoraCMS
   ══════════════════════════════════════════════════════════════
   Valida que el domini d'una petició estigui dins la llista
   de allowed_domains del payload JWT.

   Normalitza dominis internament: treu protocol, www, trailing
   slash, i path/query. localhost sempre permès.
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class JwtDomainValidator
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * Valida el Host de la petició actual contra el payload del JWT.
     *
     * @param array $payload El payload complet del JWT
     * @return bool True si el domini està permès
     */
    public function validateFromPayload(array $payload): bool
    {
        if (!isset($payload['user_id'])) {
            return true;
        }

        $allowedDomains = $payload['allowed_domains'] ?? [];
        if (!in_array('localhost', $allowedDomains, true)) {
            $allowedDomains[] = 'localhost';
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return true;
        }

        $origin = $request->headers->get('Origin');
        if ($origin) {
            $currentDomain = $this->normalize($origin);
        } else {
            $currentDomain = $this->normalize($request->getHost());
        }
        foreach ($allowedDomains as $allowed) {
            $normalizedAllowed = $this->normalize($allowed);
            if ($currentDomain === $normalizedAllowed || str_ends_with($currentDomain, '.' . $normalizedAllowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalitza un domini per a comparació consistent.
     *
     * - Treu http://, https://
     * - Treu www.
     * - Treu trailing slash
     * - Treu path/query si algú passa URL sencera
     *
     * @param string $input Domini o URL a normalitzar
     * @return string Domini net
     */
    private function normalize(string $input): string
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
}
