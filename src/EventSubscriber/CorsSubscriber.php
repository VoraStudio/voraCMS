<?php

/* ===========================================================
   CORS Subscriber — VoraCMS
   ===========================================================
   Afegeix headers CORS a totes les respostes de rutes /api/*.
   Necessari perquè el frontend (victoriaTaylor) pugui fer
   fetch des d'un origen diferent (ex: localhost:5500).

   Comportament:
   - GET /api/*        → afegeix headers CORS
   - OPTIONS /api/*    → respon 204 només amb headers (preflight)
   - Rutes NO /api/*   → no fa res
   =========================================================== */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    /* ----- INICI SECCIÓ HEADERS CORS ----- */
    /* S'executa a cada resposta. Només afegeix CORS si la ruta comença per /api/. */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        /* Ignorem rutes que no siguin de l'API */
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Max-Age' => '86400',
        ];

        /* ----- INICI SECCIÓ PREF LIGHT (OPTIONS) ----- */
        /* Els navegadors envien OPTIONS abans del GET real per verificar CORS. */
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', Response::HTTP_NO_CONTENT);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            $event->setResponse($response);

            return;
        }

        /* Afegim CORS a la resposta normal */
        $response = $event->getResponse();
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
