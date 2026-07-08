<?php

/* ══════════════════════════════════════════════════════════════
   API Auth Controller — VoraCMS
   ══════════════════════════════════════════════════════════════
   /api/auth/login  → gestionat per lexik/jwt-authentication-bundle
   /api/auth/me     → retorna dades de l'usuari autenticat (tenant)
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    /* ─── LOGIN ─── */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        // Handled by lexik/jwt-authentication-bundle via security.yaml
        throw new \LogicException('This should never be reached.');
    }

    /* ─── ME ─── */
    /* Retorna les dades de l'usuari (ara també és el tenant). */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Usuari no autenticat'], 401);
        }

        return $this->json([
            'data' => [
                'slug' => $user->getSlug(),
                'apiToken' => $user->getApiToken(),
                'company' => $user->getCompany(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'allowedDomains' => $user->getAllowedDomains() ?? [],
            ],
        ]);
    }
}
