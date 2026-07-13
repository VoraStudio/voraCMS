<?php

/* ══════════════════════════════════════════════════════════════
   API REST — Entry Controller (VoraCMS)
   ══════════════════════════════════════════════════════════════
   Endpoints públics que consumeix el frontend.
   Format de sortida: { data: { ... } } — compatible Strapi v5.

   Autenticació: Bearer token via header
   Authorization: Bearer <jwt>
   El JWT és validat per LexikJWTAuthenticator.

   Les consultes ja són scoped a l'usuari autenticat pel
   UserIdFilter / UserFilterSubscriber. No s'accepta el
   paràmetre ?client={slug}.

   Rutes:
     GET /api/{slug}        → Llistat d'entrades
     GET /api/{slug}/{id}   → Entrada individual
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Entity\User;
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
        private EntrySerializer $serializer,
    ) {}

    /* ─── LLISTAT ─── */
    /* GET /api/{slug}?locale=ca
       Retorna totes les entrades publicades d'un content type,
       scoped a l'usuari autenticat. */
    #[Route('/{slug}', name: 'api_entry_list', methods: ['GET'])]
    public function list(
        string $slug,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'No autoritzat'], 401);
        }

        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) {
            return $this->json(['error' => 'No trobat'], 404);
        }

        $locale = $request->query->get('locale');
        $entries = $entryRepo->findPublishedByType($slug, $locale);

        return $this->json([
            'data' => $this->serializer->serializeCollection($entries),
        ]);
    }

    /* ─── DETALL ─── */
    /* GET /api/{slug}/{id}
       Retorna una entrada concreta pel seu ID, scoped a l'usuari. */
    #[Route('/{slug}/{id}', name: 'api_entry_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        string $slug,
        int $id,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'No autoritzat'], 401);
        }

        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) {
            return $this->json(['error' => 'Tipus de contingut no trobat'], 404);
        }

        $entry = $entryRepo->findPublishedById($id);
        if (!$entry || $entry->getContentType()->getId() !== $contentType->getId()) {
            return $this->json(['error' => 'Entrada no trobada'], 404);
        }

        return $this->json([
            'data' => $this->serializer->serialize($entry),
        ]);
    }
}
