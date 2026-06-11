<?php

/* ══════════════════════════════════════════════════════════════
   MediaService — Pujada de fitxers amb tenant isolation
   ══════════════════════════════════════════════════════════════
   Desa els fitxers a /public/uploads/{clientId}/ en lloc
   del directori arrel pla. Això aïlla físicament els fitxers
   per client i evita col·lisions de noms entre tenants.

   Si no hi ha client al ClientScope (cas rar, p.ex. CLI
   sense --client), fa fallback al directori arrel /uploads/.
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'webp', 'avif'];
    private const MAX_FILE_SIZE = 1048576; // 1 MB
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $em,
        private ClientScope $clientScope,
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/public/uploads';
    }

    /* ─── UPLOAD ─── */
    /* Puja un fitxer al directori del client actual.
       Crea el subdirectori /uploads/{clientId}/ si no existeix.
       Assigna el client a l'entitat Media per a la traçabilitat. */
    public function upload(UploadedFile $file, ?User $user = null): Media
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException(
                "Extensió no permesa: $extension. Permeses: " . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        $fileSize = $file->getSize();

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                "Fitxer massa gran: " . ceil($fileSize / 1024) . "KB. Màxim: 1MB"
            );
        }

        $safeFilename = uniqid() . '_' . time() . '.' . $extension;

        /* ─── Directori per client ─── */
        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $clientUploadDir = $this->uploadDir . '/' . $clientId;
            if (!is_dir($clientUploadDir)) {
                mkdir($clientUploadDir, 0775, true);
            }
        } else {
            $clientUploadDir = $this->uploadDir;
        }

        /* Capturar abans de move() — el UploadedFile original
           queda apuntant al temporal que ja no existeix. */
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        $file->move($clientUploadDir, $safeFilename);

        $media = new Media();
        $media->setFilename($safeFilename);
        $media->setOriginalFilename($originalName);
        $media->setExtension($extension);
        $media->setMimeType($mimeType);

        /* ─── Path relativa amb subdirectori de client ─── */
        if ($clientId !== null) {
            $media->setPath('/uploads/' . $clientId . '/' . $safeFilename);
        } else {
            $media->setPath('/uploads/' . $safeFilename);
        }

        $media->setFileSize($fileSize);
        $media->setUploadedBy($user);

        /* ─── Assignar client a l'entitat Media ─── */
        $client = $this->clientScope->getClient();
        if ($client) {
            $media->setClient($client);
        }

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }
}
