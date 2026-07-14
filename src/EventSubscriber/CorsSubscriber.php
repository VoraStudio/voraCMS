<?php

/* ===========================================================
   CORS Subscriber — VoraCMS
   ===========================================================
   Afegeix headers CORS a totes les respostes de rutes /api/*.
   Necessari perquè el frontend pugui fer fetch des d'un
   origen diferent.

   Comportament:
   - GET /api/*        → afegeix headers CORS si l'origen és permès
   - OPTIONS /api/*    → respon 204 amb headers (preflight)
   - Origen no permès  → 403 Forbidden
   - Sense Origin      → petició same-origin, sense headers CORS
   =========================================================== */

namespace App\EventSubscriber;

use App\Contract\CorsOriginResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CorsOriginResolverInterface $resolver,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $allowedOrigins = $this->resolver->resolve($request);
        $origin = $request->headers->get('Origin');

        /* Sense Origin → petició same-origin, deixem passar */
        if ($origin === null) {
            return;
        }

        /* Sense orígens configurats → denegar tot */
        if (empty($allowedOrigins)) {
            $event->setResponse(new Response('Forbidden', Response::HTTP_FORBIDDEN));
            return;
        }

        /* Verificar si l'origen és permès */
        if (!$this->isOriginAllowed($origin, $allowedOrigins)) {
            $event->setResponse(new Response('Forbidden', Response::HTTP_FORBIDDEN));
            return;
        }

        $headers = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Max-Age' => '86400',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', Response::HTTP_NO_CONTENT);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            $event->setResponse($response);

            return;
        }

        $response = $event->getResponse();
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }

    private function isOriginAllowed(string $origin, array $allowedOrigins): bool
    {
        $origin = rtrim($origin, '/');

        foreach ($allowedOrigins as $allowed) {
            $allowed = rtrim($allowed, '/');

            if ($origin === $allowed) {
                return true;
            }

            $originHost = parse_url($origin, PHP_URL_HOST) ?: $origin;
            $allowedHost = parse_url($allowed, PHP_URL_HOST) ?: $allowed;

            // Normalització: treure www. per a comparar correctament amb o sense subdomini www
            $originHostClean = preg_replace('/^www\./i', '', $originHost);
            $allowedHostClean = preg_replace('/^www\./i', '', $allowedHost);

            if ($originHostClean === $allowedHostClean || str_ends_with($originHostClean, '.' . $allowedHostClean)) {
                return true;
            }
        }

        return false;
    }
}
