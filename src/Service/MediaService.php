<?php

/* ══════════════════════════════════════════════════════════════
   MediaService — Pujada de fitxers amb tenant isolation
   ══════════════════════════════════════════════════════════════
   Desa els fitxers a /public/uploads/{userId}/ per aïllar
   físicament els fitxers per usuari.
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

use App\Entity\Media;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
    private const MAX_FILE_SIZE = 3145728; // 3 MB
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $em,
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/public/uploads';
    }

    public function upload(UploadedFile $file, User $user, ?Project $project = null): Media
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
                "Fitxer massa gran: " . ceil($fileSize / 1024) . "KB. Màxim: 3MB"
            );
        }

        $safeFilename = uniqid() . '_' . time() . '.' . $extension;

        /* ─── Determinar l'usuari propietari: si hi ha projecte i qui puja és admin, usar el client del projecte ─── */
        $owner = $user;
        if ($project && $project->getUser()) {
            $owner = $project->getUser();
        }

        /* ─── Directori per usuari propietari ─── */
        $userUploadDir = $this->uploadDir . '/' . $owner->getId();
        if (!is_dir($userUploadDir)) {
            mkdir($userUploadDir, 0775, true);
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        $file->move($userUploadDir, $safeFilename);

        $media = new Media();
        $media->setFilename($safeFilename);
        $media->setOriginalFilename($originalName);
        $media->setExtension($extension);
        $media->setMimeType($mimeType);
        $media->setPath('/uploads/' . $owner->getId() . '/' . $safeFilename);
        $media->setFileSize($fileSize);
        $media->setUploadedBy($user);
        $media->setUser($owner);
        $media->setProject($project);

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }
}
