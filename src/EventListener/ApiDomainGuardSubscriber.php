<?php

/* ══════════════════════════════════════════════════════════════
   ApiDomainGuardSubscriber — VoraCMS
   ══════════════════════════════════════════════════════════════
   Verifica que el domini orige de la petició API estigui dins
   dels permesos per l'usuari autenticat.

   Si l'usuari té allowedDomains buit o null, es permet tot
   (comportament actual, per no trencar res).

   Si té dominis definits, l'Origin ha de coincidir.
   ══════════════════════════════════════════════════════════════ */

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
readonly class ApiDomainGuardSubscriber
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /* ── Només per a rutes API (excloem auth i admin) ── */
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api') || str_starts_with($path, '/api/auth')) {
            return;
        }

        /* ── OPTIONS preflight no es bloqueja ── */
        if ($request->isMethod('OPTIONS')) {
            return;
        }

        /* ── Usuari autenticat? ── */
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        /* ── Si no té dominis permesos, es permet tot ── */
        $allowed = $user->getAllowedDomains();
        if (empty($allowed)) {
            return;
        }

        /* ── Obtenir Origin ── */
        $origin = $request->headers->get('Origin');
        if ($origin === null) {
            return; /* Sense Origin: permetre (desenvolupament local, curl, etc.) */
        }

        /* ── Extreure domini de l'Origin ── */
        $parsed = parse_url($origin, PHP_URL_HOST);
        if ($parsed === false || $parsed === null) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Invalid Origin header'],
                403
            ));
            return;
        }

        /* ── Comprovar contra els dominis permesos ── */
        foreach ($allowed as $domain) {
            if ($parsed === $domain || str_ends_with($parsed, '.' . $domain)) {
                return; /* ✅ Permès */
            }
        }

        /* ── Blocatge ── */
        $event->setResponse(new JsonResponse(
            ['error' => 'Domain not allowed: ' . $parsed],
            403
        ));
    }
}
