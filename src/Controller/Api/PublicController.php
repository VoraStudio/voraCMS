<?php

/* ══════════════════════════════════════════════════════════════
   API Pública — PublicController (VoraCMS)
   ══════════════════════════════════════════════════════════════
   Endpoints públics (sense autenticació) perquè el frontend
   estàtic de Victoria Taylor pugui consumir dades.

   CORS i OPTIONS gestionats globalment per CorsSubscriber.
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Entity\Entry;
use App\Entity\ContentType;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Repository\ProjectRepository;
use App\Service\ArtisteTransformerService;
use App\Service\EntrySerializer;
use App\Service\TokenMasterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
class PublicController extends AbstractController
{
    public function __construct(
        private EntrySerializer $serializer,
    ) {}

    /* ─── CONTINGUT PER PROJECTE ─── */
    /* GET /api/public/{project}/{type}
       Retorna les entrades publicades d'un content type dins d'un projecte.
       Ex: /api/public/victoria-taylor/noticia
           /api/public/victoria-taylor/event
           /api/public/victoria-taylor/artistes_victoria_taylor
       Sense autenticació. CORS obert. */
    #[Route('/{project}/{type}', name: 'api_public_content', methods: ['GET'])]
    public function content(
        string $project,
        string $type,
        ProjectRepository $projectRepo,
        ContentTypeRepository $ctRepo,
        EntityManagerInterface $em,
        Request $request,
    ): JsonResponse {
        $projectEntity = $projectRepo->findBySlug($project);
        if (!$projectEntity) {
            return $this->json(['error' => 'Projecte no trobat'], 404);
        }

        $contentType = $ctRepo->findBySlug($type, $projectEntity->getId());
        if (!$contentType) {
            return $this->json(['error' => 'Tipus de contingut no trobat'], 404);
        }

        $entries = $em->createQueryBuilder()
            ->select('e')
            ->from(Entry::class, 'e')
            ->where('e.contentType = :ct')
            ->andWhere('e.status = :status')
            ->setParameter('ct', $contentType)
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $baseUrl = $request->getSchemeAndHttpHost();
        $data = $this->serializer->serializeCollection($entries, $baseUrl);

        return $this->json(['data' => $data]);
    }

    /* ─── ITEM INDIVIDUAL ─── */
    /* GET /api/public/{project}/{type}/{id}
       Retorna una entrada individual del content type dins del projecte. */
    #[Route('/{project}/{type}/{id}', name: 'api_public_content_item', methods: ['GET'])]
    public function item(
        string $project,
        string $type,
        int $id,
        ProjectRepository $projectRepo,
        ContentTypeRepository $ctRepo,
        EntityManagerInterface $em,
        Request $request,
    ): JsonResponse {
        $projectEntity = $projectRepo->findBySlug($project);
        if (!$projectEntity) {
            return $this->json(['error' => 'Projecte no trobat'], 404);
        }

        $contentType = $ctRepo->findBySlug($type, $projectEntity->getId());
        if (!$contentType) {
            return $this->json(['error' => 'Tipus de contingut no trobat'], 404);
        }

        $entry = $em->find(Entry::class, $id);
        if (!$entry || $entry->getContentType()->getId() !== $contentType->getId()) {
            return $this->json(['error' => 'Entrada no trobada'], 404);
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $data = $this->serializer->serialize($entry, $baseUrl);

        return $this->json(['data' => $data]);
    }

    /* ─── ARTISTES (format compat amb artistas.js) ─── */
    #[Route('/artistes', name: 'api_public_artistes', methods: ['GET'])]
    public function artistes(
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        ArtisteTransformerService $transformer,
        Request $request,
    ): JsonResponse {
        $contentType = $ctRepo->findBySlug('artistes_victoria_taylor');
        if (!$contentType) {
            return $this->json(['error' => 'Tipus de contingut no trobat'], 404);
        }

        $entries = $entryRepo->findPublishedByType('artistes_victoria_taylor');
        $baseUrl = $request->getSchemeAndHttpHost();
        $locale = $request->query->get('locale', 'ca');

        return $this->json($transformer->transformAll($entries, $baseUrl, $locale));
    }

    /* ─── TOKEN MASTER (JWT sense login) ─── */
    /* GET /api/public/token
       Retorna un JWT si el domini del client està autoritzat
       als allowed_domains d'algun usuari. Sense credencials. */
    #[Route('/token', name: 'api_public_token', methods: ['GET'])]
    public function token(
        Request $request,
        TokenMasterService $tokenService,
    ): JsonResponse {
        $token = $tokenService->generateToken($request->getHost());

        if (!$token) {
            return $this->json(
                ['error' => 'Domini no autoritzat'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $this->json(['token' => $token]);
    }
}
