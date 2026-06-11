<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'client')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: ContentType::class, mappedBy: 'client')]
    private Collection $contentTypes;

    #[ORM\OneToMany(targetEntity: Entry::class, mappedBy: 'client')]
    private Collection $entries;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'client')]
    private Collection $media;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->users = new ArrayCollection();
        $this->contentTypes = new ArrayCollection();
        $this->entries = new ArrayCollection();
        $this->media = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getUsers(): Collection { return $this->users; }
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setClient($this);
        }
        return $this;
    }
    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getClient() === $this) $user->setClient(null);
        }
        return $this;
    }

    public function getContentTypes(): Collection { return $this->contentTypes; }
    public function addContentType(ContentType $contentType): static
    {
        if (!$this->contentTypes->contains($contentType)) {
            $this->contentTypes->add($contentType);
            $contentType->setClient($this);
        }
        return $this;
    }
    public function removeContentType(ContentType $contentType): static
    {
        if ($this->contentTypes->removeElement($contentType)) {
            if ($contentType->getClient() === $this) $contentType->setClient(null);
        }
        return $this;
    }

    public function getEntries(): Collection { return $this->entries; }
    public function addEntry(Entry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setClient($this);
        }
        return $this;
    }
    public function removeEntry(Entry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            if ($entry->getClient() === $this) $entry->setClient(null);
        }
        return $this;
    }

    public function getMedia(): Collection { return $this->media; }
    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setClient($this);
        }
        return $this;
    }
    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            if ($medium->getClient() === $this) $medium->setClient(null);
        }
        return $this;
    }
}
