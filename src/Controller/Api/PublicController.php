<?php

/* ══════════════════════════════════════════════════════════════
   API Pública — PublicController (VoraCMS)
   ══════════════════════════════════════════════════════════════
   Endpoints públics (sense autenticació) perquè el frontend
   estàtic de Victoria Taylor pugui consumir dades.
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Api;

use App\Entity\Entry;
use App\Entity\ContentType;
use App\Repository\ContentTypeRepository;
use App\Repository\ProjectRepository;
use App\Service\EntrySerializer;
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
    #[Route('/{project}/{type}', name: 'api_public_content', methods: ['GET', 'OPTIONS'])]
    public function content(
        string $project,
        string $type,
        ProjectRepository $projectRepo,
        ContentTypeRepository $ctRepo,
        EntityManagerInterface $em,
        Request $request,
    ): JsonResponse {
        if ($request->isMethod('OPTIONS')) {
            return $this->corsResponse(204);
        }

        $projectEntity = $projectRepo->findBySlug($project);
        if (!$projectEntity) {
            return $this->json(['error' => 'Project not found'], 404, $this->corsHeaders());
        }

        $contentType = $ctRepo->findBySlug($type, $projectEntity->getId());
        if (!$contentType) {
            return $this->json(['error' => 'Content type not found'], 404, $this->corsHeaders());
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
        $data = $this->serializer->serializeCollection($entries);

        /* Resoldre URLs d'imatges a absolutes */
        $data = $this->resolveMediaUrls($data, $baseUrl);

        return $this->json(['data' => $data], Response::HTTP_OK, $this->corsHeaders());
    }

    /* ─── ITEM INDIVIDUAL ─── */
    /* GET /api/public/{project}/{type}/{id}
       Retorna una entrada individual del content type dins del projecte. */
    #[Route('/{project}/{type}/{id}', name: 'api_public_content_item', methods: ['GET', 'OPTIONS'])]
    public function item(
        string $project,
        string $type,
        int $id,
        ProjectRepository $projectRepo,
        ContentTypeRepository $ctRepo,
        EntityManagerInterface $em,
        Request $request,
    ): JsonResponse {
        if ($request->isMethod('OPTIONS')) {
            return $this->corsResponse(204);
        }

        $projectEntity = $projectRepo->findBySlug($project);
        if (!$projectEntity) {
            return $this->json(['error' => 'Project not found'], 404, $this->corsHeaders());
        }

        $contentType = $ctRepo->findBySlug($type, $projectEntity->getId());
        if (!$contentType) {
            return $this->json(['error' => 'Content type not found'], 404, $this->corsHeaders());
        }

        $entry = $em->find(Entry::class, $id);
        if (!$entry || $entry->getContentType()->getId() !== $contentType->getId()) {
            return $this->json(['error' => 'Entry not found'], 404, $this->corsHeaders());
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $data = $this->serializer->serialize($entry);
        $data = $this->resolveMediaUrls([$data], $baseUrl);

        return $this->json(['data' => $data[0]], Response::HTTP_OK, $this->corsHeaders());
    }

    /* ─── ARTISTES (format compat amb artistas.js) ─── */
    #[Route('/artistes', name: 'api_public_artistes', methods: ['GET', 'OPTIONS'])]
    public function artistes(
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        Request $request,
    ): JsonResponse {
        if ($request->isMethod('OPTIONS')) {
            return $this->corsResponse(204);
        }

        $contentType = $ctRepo->findBySlug('artistes_victoria_taylor');
        if (!$contentType) {
            return $this->json(['error' => 'Content type not found'], 404, $this->corsHeaders());
        }

        $entries = $entryRepo->findPublishedByType('artistes_victoria_taylor');
        $baseUrl = $request->getSchemeAndHttpHost();
        $result = [];

        foreach ($entries as $entry) {
            $data = $this->serializer->serialize($entry);
            $locale = $entry->getLocale() ?: 'ca';
            $titol = $data['titol'] ?? 'Artista';
            $id = $this->slugifyId($titol) . '-' . $entry->getId();

            $imgUrl = !empty($data['imatge'][0]['url'])
                ? $baseUrl . '/' . ltrim($data['imatge'][0]['url'], '/')
                : null;

            $artist = [
                'id' => $id,
                'nombre' => $this->toLang($titol, $locale),
                'rol' => $this->toLang($data['subtitol'] ?? '', $locale),
                'cardImg' => $imgUrl,
                'heroImg' => $imgUrl,
                'instagram' => null,
                'bio' => $this->toLang(
                    $data['descripcio'] ? [$data['descripcio']] : [],
                    $locale
                ),
                'logros' => $this->mapLogros($data['logros'] ?? [], $locale),
                'obras' => $this->mapObras($data['galeria'] ?? [], $baseUrl, $locale),
            ];

            $result[$id] = $artist;
        }

        return $this->json($result, Response::HTTP_OK, $this->corsHeaders());
    }

    /* ─── HELPERS ─── */

    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ];
    }

    private function corsResponse(int $status): JsonResponse
    {
        return new JsonResponse(null, $status, $this->corsHeaders());
    }

    private function resolveMediaUrls(array $data, string $baseUrl): array
    {
        foreach ($data as &$entry) {
            foreach ($entry as $key => &$value) {
                /* Image field: array of [{url, ...}] */
                if (is_array($value) && isset($value[0]['url'])) {
                    foreach ($value as &$item) {
                        if (isset($item['url']) && $item['url'] && !str_starts_with($item['url'], 'http')) {
                            $item['url'] = $baseUrl . '/' . ltrim($item['url'], '/');
                        }
                        if (isset($item['formats'])) {
                            foreach ($item['formats'] as &$fmt) {
                                if (isset($fmt['url']) && $fmt['url'] && !str_starts_with($fmt['url'], 'http')) {
                                    $fmt['url'] = $baseUrl . '/' . ltrim($fmt['url'], '/');
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function toLang(mixed $value, string $locale): object
    {
        $locales = ['es', 'ca', 'en'];
        $obj = [];
        foreach ($locales as $l) {
            $obj[$l] = $l === $locale ? $value : '';
        }
        return (object) $obj;
    }

    private function mapLogros(array $logros, string $locale): array
    {
        return array_map(function ($l) use ($locale) {
            return [
                'año' => $l['año'] ?? '',
                'textos' => $this->toLang(
                    !empty($l['texto']) ? [$l['texto']] : [],
                    $locale
                ),
            ];
        }, $logros);
    }

    private function mapObras(array $galeria, string $baseUrl, string $locale): array
    {
        return array_map(function ($o) use ($baseUrl, $locale) {
            $url = !empty($o['url'])
                ? $baseUrl . '/' . ltrim($o['url'], '/')
                : null;
            return [
                'img' => $url,
                'titulo' => $this->toLang($o['name'] ?? '', $locale),
            ];
        }, $galeria);
    }

    private function slugifyId(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(
            ['á','é','í','ó','ú','à','è','ì','ò','ù','ñ','ü'],
            ['a','e','i','o','u','a','e','i','o','u','n','u'],
            $text
        );
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
