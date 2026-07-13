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
use Symfony\Component\HttpFoundation\Request;
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
    public function me(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Usuari no autenticat'], 401);
        }

        $authHeader = $request->headers->get('Authorization', '');
        $token = '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        return $this->json([
            'data' => [
                'slug' => $user->getSlug(),
                'token' => $token,
                'company' => $user->getCompany(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'allowedDomains' => $user->getAllowedDomains() ?? [],
            ],
        ]);
    }
}
