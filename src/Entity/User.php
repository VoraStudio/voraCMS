<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', uniqueConstraints: [new UniqueConstraint(name: 'user_email_client', columns: ['email', 'client_id'])])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\Column(options: ['default' => 'ca'])]
    private ?string $locale = 'ca';

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: UserProject::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $projectPermissions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->projectPermissions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array { $roles = $this->roles; $roles[] = 'ROLE_USUARIO'; return array_unique($roles); }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getLocale(): ?string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getProjectPermissions(): Collection
    {
        return $this->projectPermissions;
    }

    public function addProjectPermission(UserProject $projectPermission): static
    {
        if (!$this->projectPermissions->contains($projectPermission)) {
            $this->projectPermissions->add($projectPermission);
            $projectPermission->setUser($this);
        }
        return $this;
    }

    public function removeProjectPermission(UserProject $projectPermission): static
    {
        if ($this->projectPermissions->removeElement($projectPermission)) {
            if ($projectPermission->getUser() === $this) {
                $projectPermission->setUser(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void {}
}
