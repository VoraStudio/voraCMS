<?php

/* ===========================================================
   UserFilterSubscriber — Activa el paràmetre del filtre
   Doctrine en cada request per aïllar dades per usuari.

   ROLE_ADMIN bypass: si l'usuari és admin, no s'aplica filtre.
   =========================================================== */

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        /* Rutes públiques — no aplicar filtre d'usuari */
        if (str_starts_with($request->getPathInfo(), '/api/public/')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if ($user === null || !is_object($user)) {
            return;
        }

        /* ROLE_ADMIN bypass — veu tot */
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return;
        }

        $userId = $user->getId();
        if ($userId === null) {
            return;
        }

        $this->entityManager
            ->getFilters()
            ->getFilter('user_id_filter')
            ->setParameter('user_id', $userId);
    }
}
