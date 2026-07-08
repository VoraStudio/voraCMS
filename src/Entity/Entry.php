<?php

namespace App\Entity;

use App\Repository\EntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntryRepository::class)]
#[ORM\Table(name: 'entries')]
#[ORM\Index(columns: ['content_type_id', 'status'])]
#[ORM\Index(columns: ['content_type_id', 'locale'])]
class Entry
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_SCHEDULED = 'scheduled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContentType $contentType = null;

    #[ORM\Column(length: 20, options: ['default' => 'draft'])]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 5, options: ['default' => 'ca'])]
    private ?string $locale = 'ca';

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $author = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: FieldValue::class, mappedBy: 'entry', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $fieldValues;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduledEndAt = null;

    public function __construct()
    {
        $this->fieldValues = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getContentType(): ?ContentType { return $this->contentType; }
    public function setContentType(?ContentType $contentType): static { $this->contentType = $contentType; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getLocale(): ?string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getFieldValues(): Collection { return $this->fieldValues; }
    public function addFieldValue(FieldValue $fieldValue): static
    {
        if (!$this->fieldValues->contains($fieldValue)) {
            $this->fieldValues->add($fieldValue);
            $fieldValue->setEntry($this);
        }
        return $this;
    }
    public function removeFieldValue(FieldValue $fieldValue): static
    {
        if ($this->fieldValues->removeElement($fieldValue)) {
            if ($fieldValue->getEntry() === $this) $fieldValue->setEntry(null);
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getPublishedAt(): ?\DateTimeInterface { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeInterface $publishedAt): static { $this->publishedAt = $publishedAt; return $this; }

    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function setScheduledAt(?\DateTimeInterface $scheduledAt): static { $this->scheduledAt = $scheduledAt; return $this; }

    public function getScheduledEndAt(): ?\DateTimeInterface { return $this->scheduledEndAt; }
    public function setScheduledEndAt(?\DateTimeInterface $scheduledEndAt): static { $this->scheduledEndAt = $scheduledEndAt; return $this; }
}
