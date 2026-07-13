<?php

/* ===========================================================
   EntrySerializer — VoraCMS
   ===========================================================
   Converteix entitats Doctrine (Entry) en arrays JSON per a l'API.
   És el cor de la transformació: converteix els FieldValues
   en un objecte pla { slug: valor } que el frontend pot consumir.

   Tipus de camp suportats:
     text, richtext → string directe
     image          → array [{ id, url, formats }]
     gallery        → array d'imatges
     boolean        → true/false
     number         → float
     date/datetime  → string ISO
     youtube        → array { id, url, embed }

   Resolució d'URLs:
     Si es passa $baseUrl, les URLs relatives d'imatges es
     converteixen a absolutes automàticament.
   =========================================================== */

namespace App\Service;

use App\Entity\Entry;
use App\Entity\FieldDefinition;
use App\Entity\FieldValue;
use App\Entity\Media;
use App\Repository\MediaRepository;

class EntrySerializer
{
    /* Cache interna per evitar múltiples queries a media amb el mateix ID */
    private array $mediaCache = [];

    public function __construct(
        private MediaRepository $mediaRepo
    ) {}

    /* ----- INICI SECCIÓ SERIALITZACIÓ PRINCIPAL ----- */
    /* Converteix una Entry en array. Itera tots els FieldValues i els aplan per slug. */
    public function serialize(Entry $entry, ?string $baseUrl = null): array
    {
        $data = [
            'id' => $entry->getId(),
            'status' => $entry->getStatus(),
            'locale' => $entry->getLocale(),
            'createdAt' => $entry->getCreatedAt()->format('c'),
            'updatedAt' => $entry->getUpdatedAt()?->format('c'),
            'publishedAt' => $entry->getPublishedAt()?->format('c'),
        ];

        /* Cada FieldDefinition té un slug únic dins del content type */
        /* Ex: titul, descripcio, data, imatge, hora, location */
        foreach ($entry->getFieldValues() as $fv) {
            $fieldDef = $fv->getFieldDefinition();
            if (!$fieldDef) continue;

            $slug = $fieldDef->getSlug();
            $data[$slug] = $this->serializeValue($fv, $fieldDef);
        }

        if ($baseUrl !== null) {
            $data = $this->resolveMediaUrls([$data], $baseUrl);
            return $data[0];
        }

        return $data;
    }

    /* Serialitza un array d'entrades (per al llistat) */
    public function serializeCollection(array $entries, ?string $baseUrl = null): array
    {
        $data = array_map(fn(Entry $e) => $this->serialize($e), $entries);

        if ($baseUrl !== null) {
            $data = $this->resolveMediaUrls($data, $baseUrl);
        }

        return $data;
    }

    /* ----- INICI SECCIÓ RESOLUCIÓ D'URLS ----- */
    /* Converteix URLs relatives d'imatges a absolutes usant $baseUrl.
       Detecta camps d'imatge per l'estructura [{url, formats}] i
       camps de galeria pel mateix patró. */
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

    /* ----- INICI SECCIÓ SERIALITZACIÓ PER TIPUS ----- */
    /* Cada tipus de camp es serialitza de forma diferent. */
    private function serializeValue(FieldValue $fv, FieldDefinition $fieldDef): mixed
    {
        $value = $fv->getValue();
        $type = $fieldDef->getFieldType();

        return match ($type) {
            FieldDefinition::TYPE_IMAGE => $this->serializeImage($value),
            FieldDefinition::TYPE_GALLERY => $this->serializeGallery($value),
            FieldDefinition::TYPE_YOUTUBE => $this->serializeYoutube($value),
            FieldDefinition::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            FieldDefinition::TYPE_NUMBER => is_numeric($value) ? (float) $value : null,
            FieldDefinition::TYPE_DATE => $this->serializeDate($value),
            FieldDefinition::TYPE_DATE_RANGE => $this->serializeDateRange($value),
            FieldDefinition::TYPE_REPEATER => $this->serializeRepeater($value),
            default => $value,
        };
    }

    /* Cacheja medias per ID per evitar N+1 queries */
    private function resolveMedia(int $id): ?Media
    {
        if (!isset($this->mediaCache[$id])) {
            $this->mediaCache[$id] = $this->mediaRepo->find($id);
        }
        return $this->mediaCache[$id];
    }

    /* ----- INICI SECCIÓ IMATGE ----- */
    /* Retorna array amb url + formats (compatibilitat Strapi).
       Si el valor és numèric, el resol com a ID de Media.
       Si no, el tracta com a URL directa (fallback). */
    private function serializeImage(?string $value): ?array
    {
        if (!$value) return null;

        if (is_numeric($value)) {
            $media = $this->resolveMedia((int) $value);
            if ($media) {
                return [
                    [
                        'id' => $media->getId(),
                        'name' => $media->getOriginalFilename(),
                        'url' => $media->getPath(),
                        'formats' => [
                            'small' => ['url' => $media->getPath()],
                            'thumbnail' => ['url' => $media->getPath()],
                        ],
                    ],
                ];
            }
        }

        /* Fallback: si el valor és una URL directa */
        return [
            [
                'id' => 0,
                'url' => $value,
                'formats' => [
                    'small' => ['url' => $value],
                    'thumbnail' => ['url' => $value],
                ],
            ],
        ];
    }

    /* ----- INICI SECCIÓ GALERIA (múltiples imatges) ----- */
    /* El valor arriba com a string d'IDs separats per comes: "1,3,5" */
    private function serializeGallery(?string $value): array
    {
        if (!$value) return [];
        $ids = array_filter(explode(',', $value));
        $images = [];

        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $media = $this->resolveMedia((int) $id);
                if ($media) {
                    $images[] = [
                        'id' => $media->getId(),
                        'name' => $media->getOriginalFilename(),
                        'url' => $media->getPath(),
                        'formats' => [
                            'small' => ['url' => $media->getPath()],
                        ],
                    ];
                    continue;
                }
            }
            $images[] = [
                'id' => 0,
                'url' => $id,
                'formats' => [
                    'small' => ['url' => $id],
                ],
            ];
        }

        return $images;
    }

    /* ----- INICI SECCIÓ DATA ----- */
    /* Normalitza el format datetime-local (YYYY-MM-DDTHH:mm) afegint :00 */
    private function serializeDate(?string $value): ?string
    {
        if (!$value) return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        return $value;
    }

    /* Serialitza un date_range (JSON {start, end}) com a {start, end} */
    private function serializeDateRange(?string $value): ?array
    {
        if (!$value) return null;
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) return null;
        return [
            'start' => $this->serializeDate($decoded['start'] ?? null),
            'end' => $this->serializeDate($decoded['end'] ?? null),
        ];
    }

    /* ----- INICI SECCIÓ REPEATER ----- */
    /* Converteix JSON string → array per al frontend */
    private function serializeRepeater(?string $value): array
    {
        if (!$value) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /* ----- INICI SECCIÓ YOUTUBE ----- */
    /* Retorna array amb ID, url de watch i url d'embed */
    private function serializeYoutube(?string $value): ?array
    {
        if (!$value) return null;

        return [
            'id' => $value,
            'url' => 'https://www.youtube.com/watch?v=' . $value,
            'embed' => 'https://www.youtube.com/embed/' . $value,
        ];
    }
}
