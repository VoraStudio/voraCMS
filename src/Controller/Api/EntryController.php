<?php

/* ===========================================================
   API REST — Entry Controller (VoraCMS)
   ===========================================================
   Endpoints públics que consumeix el frontend (victoriaTaylor).
   Format de sortida: { data: { ... } } — compatible Strapi v5.

   Rutes:
     GET /api/{slug}        → Llistat d'entrades publicades d'un tipus
     GET /api/{slug}/{id}   → Entrada individual per ID
   =========================================================== */

namespace App\Controller\Api;

use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Service\EntrySerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class EntryController extends AbstractController
{
    public function __construct(
        private EntrySerializer $serializer,  /* Converteix entitats Doctrine a JSON */
    ) {}

    /* ----- INICI SECCIÓ LLISTAT ----- */
    /* GET /api/{slug}?locale=ca — Retorna totes les entrades publicades d'un content type. */
    /* Ex: /api/noticia → llistat de notícies, /api/event → llistat d'events */
    #[Route('/{slug}', name: 'api_entry_list', methods: ['GET'])]
    public function list(
        string $slug,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        Request $request
    ): JsonResponse {
        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $locale = $request->query->get('locale');
        $entries = $entryRepo->findPublishedByType($slug, $locale);

        return $this->json([
            'data' => $this->serializer->serializeCollection($entries),
        ]);
    }

    /* ----- INICI SECCIÓ DETALL ----- */
    /* GET /api/{slug}/{id} — Retorna una entrada concreta pel seu ID. */
    /* Ex: /api/noticia/5 → noticia amb ID 5 */
    #[Route('/{slug}/{id}', name: 'api_entry_show', methods: ['GET'])]
    public function show(
        string $slug,
        int $id,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo
    ): JsonResponse {
        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) {
            return $this->json(['error' => 'Content type not found'], 404);
        }

        $entry = $entryRepo->findPublishedById($id);
        if (!$entry || $entry->getContentType()->getId() !== $contentType->getId()) {
            return $this->json(['error' => 'Entry not found'], 404);
        }

        return $this->json([
            'data' => $this->serializer->serialize($entry),
        ]);
    }
}
