<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure]
class TokenGenerator
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(int $length = 12): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $max)];
        }

        return $token;
    }
}
