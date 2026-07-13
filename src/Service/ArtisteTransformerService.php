<?php

/* ===========================================================
   ArtisteTransformerService — VoraCMS
   ===========================================================
   Transforma entrades del content type "artistes_victoria_taylor"
   al format que espera el frontend Victoria Taylor
   (artistas.js). Converteix camps multilingües a l'estructura
   { es, ca, en } i resol URLs d'imatges a absolutes.
   =========================================================== */

namespace App\Service;

use App\Entity\Entry;

readonly class ArtisteTransformerService
{
    public function __construct(
        private EntrySerializer $serializer,
    ) {}

    /* ----- INICI SECCIÓ TRANSFORMACIÓ PRINCIPAL ----- */
    /**
     * @param Entry[] $entries
     * @return array format compatible amb artistas.js
     */
    public function transformAll(array $entries, string $baseUrl, string $locale): array
    {
        $result = [];
        foreach ($entries as $entry) {
            $data = $this->serializer->serialize($entry, $baseUrl);
            $titol = $data['titol'] ?? 'Artista';
            $id = $this->slugifyId($titol) . '-' . $entry->getId();

            $imgUrl = !empty($data['imatge'][0]['url'])
                ? $baseUrl . '/' . ltrim($data['imatge'][0]['url'], '/')
                : null;

            $result[$id] = [
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
        }
        return $result;
    }

    /* ----- INICI SECCIÓ HELPERS D'IDIOMA ----- */
    /* Converteix un valor al format multilingüe { es, ca, en }
       on només l'idioma actiu té el valor. */
    private function toLang(mixed $value, string $locale): object
    {
        $locales = ['es', 'ca', 'en'];
        $obj = [];
        foreach ($locales as $l) {
            $obj[$l] = $l === $locale ? $value : '';
        }
        return (object) $obj;
    }

    /* ----- INICI SECCIÓ HELPERS DE CAMP ----- */
    /* Transforma l'array de logros al format { año, textos } */
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

    /* Transforma l'array de galeria al format { img, titulo } */
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

    /* Genera un slug a partir del títol per fer-lo servir com a ID */
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
