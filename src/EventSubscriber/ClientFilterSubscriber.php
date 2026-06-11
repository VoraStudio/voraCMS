<?php

/* ===========================================================
   ClientFilterSubscriber — Activa el paràmetre del filtre
   Doctrine en cada request per aïllar dades per client.

   Escolta KernelEvents::CONTROLLER (després de resoldre
   autenticació JWT en rutes API, abans que el controlador
   executi cap query).
   =========================================================== */

namespace App\EventSubscriber;

use App\Service\ClientScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ClientFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ClientScope $clientScope,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            /* KernelEvents::CONTROLLER és tardà perquè el JWT ja s'ha
               desxifrat i ClientScope pot llegir el client_id del token. */
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    /* -----------------------------------------------------------
       onController — S'executa just abans de l'acció del controlador
       Activa el paràmetre del filtre si hi ha client_id actiu.
       Si l'usuari és super-admin, getClientId() retorna null
       i el filtre no s'aplica (bypass total).
       ----------------------------------------------------------- */
    public function onController(ControllerEvent $event): void
    {
        $clientId = $this->clientScope->getClientId();

        if ($clientId === null) {
            /* Super-admin o sense context: el filtre no rep paràmetre
               → addFilterConstraint() retorna '' → sense WHERE extra */
            return;
        }

        /* Activa el paràmetre del filtre. El filtre ja està enabled:true
           a doctrine.yaml; aquí només li passem el valor per aquest request */
        $this->entityManager
            ->getFilters()
            ->getFilter('client_id_filter')
            ->setParameter('client_id', $clientId);
    }
}
