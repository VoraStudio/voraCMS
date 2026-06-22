<?php

namespace App\Entity;

use App\Repository\UserProjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProjectRepository::class)]
#[ORM\Table(name: 'user_project')]
#[ORM\UniqueConstraint(name: 'user_project_unique', columns: ['user_id', 'project_id'])]
class UserProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'projectPermissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userPermissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $canManageContentTypes = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function canManageContentTypes(): bool
    {
        return $this->canManageContentTypes;
    }

    public function setCanManageContentTypes(bool $canManageContentTypes): static
    {
        $this->canManageContentTypes = $canManageContentTypes;
        return $this;
    }
}
