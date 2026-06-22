<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
#[ORM\UniqueConstraint(name: 'project_slug_client', columns: ['slug', 'client_id'])]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#4945FF'])]
    private ?string $color = '#4945FF';

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToMany(targetEntity: ContentType::class, mappedBy: 'project', cascade: ['persist'])]
    private Collection $contentTypes;

    #[ORM\OneToMany(targetEntity: UserProject::class, mappedBy: 'project', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $userPermissions;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->contentTypes = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getContentTypes(): Collection { return $this->contentTypes; }
    public function addContentType(ContentType $contentType): static
    {
        if (!$this->contentTypes->contains($contentType)) {
            $this->contentTypes->add($contentType);
            $contentType->setProject($this);
        }
        return $this;
    }
    public function removeContentType(ContentType $contentType): static
    {
        if ($this->contentTypes->removeElement($contentType)) {
            if ($contentType->getProject() === $this) $contentType->setProject(null);
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function addUserPermission(UserProject $userPermission): static
    {
        if (!$this->userPermissions->contains($userPermission)) {
            $this->userPermissions->add($userPermission);
            $userPermission->setProject($this);
        }
        return $this;
    }

    public function removeUserPermission(UserProject $userPermission): static
    {
        if ($this->userPermissions->removeElement($userPermission)) {
            if ($userPermission->getProject() === $this) {
                $userPermission->setProject(null);
            }
        }
        return $this;
    }
}
