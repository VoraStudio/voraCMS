<?php

/* ══════════════════════════════════════════════════════════════
   API REST — Section Controller (VoraCMS)
   ══════════════════════════════════════════════════════════════
   Endpoint de descoberta de seccions per al frontend.

   GET /api/sections[?active=true|false]
   Retorna els ContentType de l'usuari autenticat, filtrats per
   l'estat actiu si s'indica.
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\ContentTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class SectionController extends AbstractController
{
    /* ─── LLISTAT DE SECCIONS ─── */
    /* GET /api/sections?active=true|false */
    #[Route('/sections', name: 'api_sections', methods: ['GET'], priority: 10)]
    public function index(Request $request, ContentTypeRepository $ctRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $active = $request->query->get('active');
        $activeFilter = $active !== null ? filter_var($active, FILTER_VALIDATE_BOOLEAN) : true;

        $sections = $ctRepo->findByUser($user->getId(), $activeFilter);

        $data = array_map(static fn ($ct) => [
            'id' => $ct->getId(),
            'name' => $ct->getName(),
            'slug' => $ct->getSlug(),
            'description' => $ct->getDescription(),
            'isActive' => $ct->isActive(),
        ], $sections);

        return $this->json(['data' => $data]);
    }
}
