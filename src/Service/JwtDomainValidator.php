<?php

/* ══════════════════════════════════════════════════════════════
   JwtDomainValidator — VoraCMS
   ══════════════════════════════════════════════════════════════
   Servei encarregat de validar que el domini d'una petició
   estigui dins la llista de allowed_domains.

   S'usa des de JwtClientIdSubscriber per validar cada petició
   JWT. localhost sempre està permès.
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class JwtDomainValidator
{
    public function __construct(
        private RequestStack $requestStack,
        private DomainService $domainService,
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

        $currentDomain = $this->domainService->normalize($request->getHost());
        foreach ($allowedDomains as $allowed) {
            $normalizedAllowed = $this->domainService->normalize($allowed);
            if ($currentDomain === $normalizedAllowed || str_ends_with($currentDomain, '.' . $normalizedAllowed)) {
                return true;
            }
        }

        return false;
    }
}
