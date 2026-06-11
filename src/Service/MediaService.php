<?php

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
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/public/uploads';
    }

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

        // Capturar tot ANTES de $file->move() perquè move() retorna un nou objecte
        // i el UploadedFile original queda apuntant al temporal (que ja no existeix)
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        $file->move($this->uploadDir, $safeFilename);

        $media = new Media();
        $media->setFilename($safeFilename);
        $media->setOriginalFilename($originalName);
        $media->setExtension($extension);
        $media->setMimeType($mimeType);
        $media->setPath('/uploads/' . $safeFilename);
        $media->setFileSize($fileSize);
        $media->setUploadedBy($user);

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }
}
