<?php

namespace App\Entity;

use App\Repository\FieldValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FieldValueRepository::class)]
#[ORM\Table(name: 'field_values')]
class FieldValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fieldValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entry $entry = null;

    #[ORM\ManyToOne(inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FieldDefinition $fieldDefinition = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    public function getId(): ?int { return $this->id; }

    public function getEntry(): ?Entry { return $this->entry; }
    public function setEntry(?Entry $entry): static { $this->entry = $entry; return $this; }

    public function getFieldDefinition(): ?FieldDefinition { return $this->fieldDefinition; }
    public function setFieldDefinition(?FieldDefinition $fieldDefinition): static { $this->fieldDefinition = $fieldDefinition; return $this; }

    public function getValue(): ?string { return $this->value; }
    public function setValue(?string $value): static { $this->value = $value; return $this; }
}
