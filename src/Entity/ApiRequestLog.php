<?php

/* ===========================================================
   ApiRequestLog — Registre de totes les crides a l'API.
   S'usa per monitoritzar tràfic, errors i ús de tokens.
   Cada crida a /api/* genera una entrada en aquesta taula.
   =========================================================== */

namespace App\Entity;

use App\Entity\Project;
use App\Repository\ApiRequestLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiRequestLogRepository::class)]
#[ORM\Index(columns: ['domain', 'created_at'])]
#[ORM\Index(columns: ['project_id', 'created_at'])]
class ApiRequestLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    private ?string $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $endpoint = null;

    #[ORM\Column(length: 10)]
    private ?string $method = null;

    #[ORM\Column]
    private ?int $statusCode = null;

    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $referer = null;

    #[ORM\Column(nullable: true)]
    private ?bool $granted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $denyReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenJti = null;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $xForwardedFor = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getDomain(): ?string { return $this->domain; }
    public function setDomain(string $domain): static { $this->domain = $domain; return $this; }

    public function getEndpoint(): ?string { return $this->endpoint; }
    public function setEndpoint(string $endpoint): static { $this->endpoint = $endpoint; return $this; }

    public function getMethod(): ?string { return $this->method; }
    public function setMethod(string $method): static { $this->method = $method; return $this; }

    public function getStatusCode(): ?int { return $this->statusCode; }
    public function setStatusCode(int $statusCode): static { $this->statusCode = $statusCode; return $this; }

    public function getIp(): ?string { return $this->ip; }
    public function setIp(string $ip): static { $this->ip = $ip; return $this; }

    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $userAgent): static { $this->userAgent = $userAgent; return $this; }

    public function getOrigin(): ?string { return $this->origin; }
    public function setOrigin(?string $origin): static { $this->origin = $origin; return $this; }

    public function getReferer(): ?string { return $this->referer; }
    public function setReferer(?string $referer): static { $this->referer = $referer; return $this; }

    public function isGranted(): ?bool { return $this->granted; }
    public function setGranted(?bool $granted): static { $this->granted = $granted; return $this; }

    public function getDenyReason(): ?string { return $this->denyReason; }
    public function setDenyReason(?string $denyReason): static { $this->denyReason = $denyReason; return $this; }

    public function getTokenJti(): ?string { return $this->tokenJti; }
    public function setTokenJti(?string $tokenJti): static { $this->tokenJti = $tokenJti; return $this; }

    public function getResponseTimeMs(): ?int { return $this->responseTimeMs; }
    public function setResponseTimeMs(?int $responseTimeMs): static { $this->responseTimeMs = $responseTimeMs; return $this; }

    public function getXForwardedFor(): ?string { return $this->xForwardedFor; }
    public function setXForwardedFor(?string $xForwardedFor): static { $this->xForwardedFor = $xForwardedFor; return $this; }

    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $errorMessage): static { $this->errorMessage = $errorMessage; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
