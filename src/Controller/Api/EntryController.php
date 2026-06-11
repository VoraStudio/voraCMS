<?php

/* ══════════════════════════════════════════════════════════════
   API REST — Entry Controller (VoraCMS)
   ══════════════════════════════════════════════════════════════
   Endpoints públics que consumeix el frontend.
   Format de sortida: { data: { ... } } — compatible Strapi v5.

   Tenant isolation: els endpoints públics no tenen JWT,
   així que el client s'identifica via query parameter
   ?client={slug}. Ex: /api/noticia?client=victoria-taylor

   Flux de scoping:
     1. Llegir ?client={slug} del query string
     2. Resoldre el Client per slug via ClientRepository
     3. Activar ClientScope::setClient() → els repositoris
        (EntryRepository, ContentTypeRepository) llegeixen
        clientScope->getClientId() i filtren les queries.
     4. Si ?client no es passa → 400 error

   Rutes:
     GET /api/{slug}?client={slug}        → Llistat d'entrades
     GET /api/{slug}/{id}?client={slug}   → Entrada individual
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Service\ClientScope;
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
        private ClientScope $clientScope,
        private ClientRepository $clientRepo,
    ) {}

    /* ─── Helper: resol el client des del query parameter ─── */
    /* Busca el Client per slug al query string i l'activa al
       ClientScope. Si no es passa el paràmetre, retorna 400.
       Si el slug no correspon a cap client, retorna 404. */
    private function resolveClientFromQuery(Request $request): JsonResponse|Client
    {
        $clientSlug = $request->query->get('client');

        if (!$clientSlug) {
            return $this->json(
                ['error' => 'Client slug is required. Use ?client={slug}'],
                400
            );
        }

        $client = $this->clientRepo->findBySlug($clientSlug);
        if (!$client) {
            return $this->json(
                ['error' => "Client '$clientSlug' not found"],
                404
            );
        }

        $this->clientScope->setClient($client);
        return $client;
    }

    /* ─── LLISTAT ─── */
    /* GET /api/{slug}?client={slug}&locale=ca
       Retorna totes les entrades publicades d'un content type,
       scoped al client indicat al query parameter. */
    #[Route('/{slug}', name: 'api_entry_list', methods: ['GET'])]
    public function list(
        string $slug,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        Request $request
    ): JsonResponse {
        $client = $this->resolveClientFromQuery($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

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

    /* ─── DETALL ─── */
    /* GET /api/{slug}/{id}?client={slug}
       Retorna una entrada concreta pel seu ID, scoped al client. */
    #[Route('/{slug}/{id}', name: 'api_entry_show', methods: ['GET'])]
    public function show(
        string $slug,
        int $id,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        Request $request
    ): JsonResponse {
        $client = $this->resolveClientFromQuery($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

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
