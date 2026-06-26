<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class SlugGenerator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function generate(string $input, ?int $excludeId = null): string
    {
        $slug = $this->slugify($input);
        $original = $slug;
        $suffix = 1;

        while ($this->exists($slug, $excludeId)) {
            $slug = sprintf('%s-%d', $original, ++$suffix);
        }

        return $slug;
    }

    private function slugify(string $input): string
    {
        $input = trim($input);
        $input = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        $input = strtolower($input);
        $input = preg_replace('/[^a-z0-9]+/', '-', $input);
        $input = trim($input, '-');

        return $input === '' ? 'user' : $input;
    }

    private function exists(string $slug, ?int $excludeId): bool
    {
        $user = $this->userRepository->findOneBy(['slug' => $slug]);

        return $user instanceof User && $user->getId() !== $excludeId;
    }
}
