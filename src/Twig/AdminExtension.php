<?php

namespace App\Twig;

use App\Repository\ContentTypeRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(private ContentTypeRepository $ctRepo) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_content_types', [$this, 'getContentTypes']),
        ];
    }

    public function getContentTypes(): array
    {
        return $this->ctRepo->findActive();
    }
}
