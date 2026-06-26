<?php

/* ===========================================================
   Visit — Registre de visites a les entrades/publicacions.
   S'usa per mostrar mètriques d'activitat al dashboard d'admin.
   Cada visita s'enregistra via POST /api/visit des del frontend.
   =========================================================== */

namespace App\Entity;

use App\Repository\VisitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitRepository::class)]
#[ORM\Index(columns: ['user_id', 'visited_at'])]
class Visit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    private ?Entry $entry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $visitedAt = null;

    public function __construct()
    {
        $this->visitedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getEntry(): ?Entry { return $this->entry; }
    public function setEntry(?Entry $entry): static { $this->entry = $entry; return $this; }

    public function getPath(): ?string { return $this->path; }
    public function setPath(?string $path): static { $this->path = $path; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $ip): static { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function getVisitedAt(): ?\DateTimeImmutable { return $this->visitedAt; }
    public function setVisitedAt(\DateTimeImmutable $visitedAt): static { $this->visitedAt = $visitedAt; return $this; }
}
