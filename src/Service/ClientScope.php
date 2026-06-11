<?php

namespace App\Service;

use App\Entity\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClientScope
{
    private ?Client $explicitClient = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public function setClient(?Client $client): void
    {
        $this->explicitClient = $client;
    }

    public function getClient(): ?Client
    {
        // (1) Explicit set — CLI, migrations, or manual override
        if ($this->explicitClient !== null) {
            return $this->explicitClient;
        }

        // (4) Super-admin context — bypass all scoping
        if ($this->isSuperAdmin()) {
            return null;
        }

        // (2) Request attribute from admin session
        // (3) Token client_id from JWT — Phase 2
        return null;
    }

    public function getClientId(): ?int
    {
        if ($this->explicitClient !== null) {
            return $this->explicitClient->getId();
        }

        if ($this->isSuperAdmin()) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            // (2) Request attribute set by admin context listener
            $clientId = $request->attributes->getInt('_client_id');
            if ($clientId) {
                return $clientId;
            }

            // (2) Session variable set by admin login
            if ($request->hasSession()) {
                $clientId = $request->getSession()->get('client_id');
                if ($clientId) {
                    return (int) $clientId;
                }
            }
        }

        // (3) JWT token client_id — Phase 2
        return null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');
    }

    public function hasClient(): bool
    {
        return $this->getClient() !== null;
    }
}
