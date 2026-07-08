<?php

namespace App\Entity;

use App\Repository\FieldDefinitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FieldDefinitionRepository::class)]
#[ORM\Table(name: 'field_definitions')]
class FieldDefinition
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_RICHTEXT = 'richtext';
    public const TYPE_IMAGE = 'image';
    public const TYPE_GALLERY = 'gallery';
    public const TYPE_DATE = 'date';
    public const TYPE_DATE_RANGE = 'date_range';
    public const TYPE_LOCATION = 'location';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_NUMBER = 'number';
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';
    public const TYPE_COLOR = 'color';
    public const TYPE_YOUTUBE = 'youtube';
    public const TYPE_REPEATER = 'repeater';
    public const TYPE_SELECT = 'select';

    public static function getTypes(): array
    {
        return [
            self::TYPE_TEXT, self::TYPE_TEXTAREA, self::TYPE_RICHTEXT,
            self::TYPE_IMAGE, self::TYPE_GALLERY,
            self::TYPE_DATE, self::TYPE_DATE_RANGE,
            self::TYPE_LOCATION, self::TYPE_BOOLEAN,
            self::TYPE_NUMBER, self::TYPE_URL, self::TYPE_EMAIL, self::TYPE_COLOR,
            self::TYPE_YOUTUBE, self::TYPE_REPEATER, self::TYPE_SELECT,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContentType $contentType = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $slug = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(callback: 'getTypes')]
    private ?string $fieldType = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $required = false;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $translatable = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $helpText = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: FieldValue::class, mappedBy: 'fieldDefinition', cascade: ['remove'])]
    private Collection $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getContentType(): ?ContentType { return $this->contentType; }
    public function setContentType(?ContentType $contentType): static { $this->contentType = $contentType; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getFieldType(): ?string { return $this->fieldType; }
    public function setFieldType(string $fieldType): static { $this->fieldType = $fieldType; return $this; }

    public function isRequired(): ?bool { return $this->required; }
    public function setRequired(bool $required): static { $this->required = $required; return $this; }

    public function isTranslatable(): ?bool { return $this->translatable; }
    public function setTranslatable(bool $translatable): static { $this->translatable = $translatable; return $this; }

    public function getHelpText(): ?string { return $this->helpText; }
    public function setHelpText(?string $helpText): static { $this->helpText = $helpText; return $this; }

    public function getSortOrder(): ?int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }

    public function getValues(): Collection { return $this->values; }

    /** Obté les opcions d'un camp select */
    public function getSelectOptions(): array
    {
        if ($this->fieldType !== self::TYPE_SELECT || !$this->helpText) {
            return [];
        }
        return array_map('trim', explode("\n", $this->helpText));
    }
}
