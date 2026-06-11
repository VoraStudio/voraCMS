<?php

/* ══════════════════════════════════════════════════════════════
   API Auth Controller — VoraCMS
   ══════════════════════════════════════════════════════════════
   /api/auth/login  → gestionat per lexik/jwt-authentication-bundle
   /api/auth/me     → retorna dades de l'usuari autenticat + client
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
    /* Retorna id, email, name, roles + client object amb
       id, name, slug. El client_id ja ve al JWT (Phase 2),
       així que el frontend pot saber a quin client pertany
       l'usuari sense necessitat del query parameter. */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ];

        /* ─── Client info ─── */
        if ($user instanceof User && $user->getClient()) {
            $data['client'] = [
                'id' => $user->getClient()->getId(),
                'name' => $user->getClient()->getName(),
                'slug' => $user->getClient()->getSlug(),
            ];
        }

        return $this->json([
            'data' => $data,
        ]);
    }
}
