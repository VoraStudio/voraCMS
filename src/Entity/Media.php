<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 10)]
    private ?string $extension = null;

    #[ORM\Column(length: 50)]
    private ?string $mimeType = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailPath = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $altText = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $uploadedBy = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }
    public function getOriginalFilename(): ?string { return $this->originalFilename; }
    public function setOriginalFilename(string $originalFilename): static { $this->originalFilename = $originalFilename; return $this; }
    public function getExtension(): ?string { return $this->extension; }
    public function setExtension(string $extension): static { $this->extension = $extension; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $mimeType): static { $this->mimeType = $mimeType; return $this; }
    public function getPath(): ?string { return $this->path; }
    public function setPath(string $path): static { $this->path = $path; return $this; }
    public function getThumbnailPath(): ?string { return $this->thumbnailPath; }
    public function setThumbnailPath(?string $thumbnailPath): static { $this->thumbnailPath = $thumbnailPath; return $this; }
    public function getFileSize(): ?int { return $this->fileSize; }
    public function setFileSize(int $fileSize): static { $this->fileSize = $fileSize; return $this; }
    public function getAltText(): ?string { return $this->altText; }
    public function setAltText(?string $altText): static { $this->altText = $altText; return $this; }
    public function getUploadedBy(): ?User { return $this->uploadedBy; }
    public function setUploadedBy(?User $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
