<?php

namespace App\Entity;

use App\Repository\ContentTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContentTypeRepository::class)]
#[ORM\Table(name: 'content_types', uniqueConstraints: [new UniqueConstraint(name: 'ct_slug_client', columns: ['slug', 'client_id'])])]
class ContentType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $base = false;

    #[ORM\ManyToOne(inversedBy: 'contentTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToMany(targetEntity: FieldDefinition::class, mappedBy: 'contentType', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $fields;

    #[ORM\OneToMany(targetEntity: Entry::class, mappedBy: 'contentType', cascade: ['remove'])]
    private Collection $entries;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->entries = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function isBase(): bool { return $this->base; }
    public function setBase(bool $base): static { $this->base = $base; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getFields(): Collection { return $this->fields; }
    public function addField(FieldDefinition $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setContentType($this);
        }
        return $this;
    }
    public function removeField(FieldDefinition $field): static
    {
        if ($this->fields->removeElement($field)) {
            if ($field->getContentType() === $this) $field->setContentType(null);
        }
        return $this;
    }

    public function getEntries(): Collection { return $this->entries; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
