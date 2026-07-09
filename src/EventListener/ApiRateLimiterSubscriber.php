<?php

/* ══════════════════════════════════════════════════════════════
   ApiRateLimiterSubscriber — VoraCMS
   ══════════════════════════════════════════════════════════════
   Limita el tráfico de la API pública (/api/*) a 60 peticiones
   por minuto por IP (o por usuario autenticado si lo está).

   Excluye:
     - /api/auth (tiene su propio login_throttling)
     - OPTIONS preflight (CORS)

   Añade headers X-RateLimit-* en todas las respuestas API para
   que los clientes puedan saber su estado de límite actual.
   ══════════════════════════════════════════════════════════════ */

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
#[AsEventListener(event: KernelEvents::RESPONSE)]
readonly class ApiRateLimiterSubscriber
{
    public function __construct(
        #[Autowire(service: 'limiter.api')]
        private RateLimiterFactory $apiRateLimiterFactory,
        private TokenStorageInterface $tokenStorage,
    ) {}

    /* ── REQUEST: comprovar límit abans de processar ── */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /* ── Només per a rutes API (excloem auth i admin) ── */
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api')) {
            return;
        }

        /* ── Excloure auth login i OPTIONS preflight ── */
        if (str_starts_with($path, '/api/auth') || $request->isMethod('OPTIONS')) {
            return;
        }

        /* ── Clau composta: IP + user ID (si autenticat) ── */
        $key = $request->getClientIp();

        $token = $this->tokenStorage->getToken();
        if ($token !== null && $token->getUser() !== null) {
            $user = $token->getUser();
            if (method_exists($user, 'getId') && $user->getId() !== null) {
                $key .= '-' . $user->getId();
            }
        }

        $limiter = $this->apiRateLimiterFactory->create($key);
        $state = $limiter->consume(1);

        /* ── Guardar estat al request per al RESPONSE event ── */
        $request->attributes->set('_rate_limiter_state', $state);
        $request->attributes->set('_rate_limiter_headers', [
            'X-RateLimit-Limit' => $state->getLimit(),
            'X-RateLimit-Remaining' => $state->getRemainingTokens(),
            'X-RateLimit-Reset' => $state->getRetryAfter()->getTimestamp(),
        ]);

        if (!$state->isAccepted()) {
            $retryAfter = $state->getRetryAfter()->getTimestamp() - time();
            $event->setResponse(new JsonResponse(
                [
                    'error' => 'Too many requests',
                    'message' => 'Has superat el límit de peticions. Intenta-ho de nou en ' . $retryAfter . ' segons.',
                    'retryAfter' => $retryAfter,
                ],
                429,
                [
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $state->getLimit(),
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Reset' => $state->getRetryAfter()->getTimestamp(),
                ]
            ));
        }
    }

    /* ── RESPONSE: afegir headers a totes les respostes API ── */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $headers = $request->attributes->get('_rate_limiter_headers');
        if ($headers === null) {
            return;
        }

        $response = $event->getResponse();
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
    }
}
