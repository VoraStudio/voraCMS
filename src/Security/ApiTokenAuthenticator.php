<?php

/* ══════════════════════════════════════════════════════════════
   API Token Authenticator — VoraCMS
   ══════════════════════════════════════════════════════════════
   Authenticator stateless per a l'API pública del frontend.
   Accepta tant apiTokens (Bearer nanoid 32 chars) com JWTs
   (Bearer header.payload.signature) sota /api/*.

   L'apiToken es valida contra l'entitat User. Els JWTs es deleguen
   a l'authenticador de LexikJWT per mantenir el comportament
   existent de l'admin.
   ══════════════════════════════════════════════════════════════ */

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private JWTAuthenticator $jwtAuthenticator,
    ) {}

    public function supports(Request $request): ?bool
    {
        $authorization = $request->headers->get('Authorization');

        return $authorization !== null && str_starts_with($authorization, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization'), 7);

        if ($this->isJwt($token)) {
            return $this->jwtAuthenticator->authenticate($request);
        }

        if ($token === '' || strlen($token) < 16) {
            throw new BadCredentialsException('Invalid API token');
        }

        $user = $this->userRepository->findByApiToken($token);

        if ($user === null) {
            throw new UserNotFoundException('API token not found');
        }

        if ($user->isActive() === false) {
            throw new BadCredentialsException('User is inactive');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), static fn (string $userIdentifier): User => $user)
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if ($this->isJwtPassport($passport)) {
            return $this->jwtAuthenticator->createToken($passport, $firewallName);
        }

        return parent::createToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $token = substr($request->headers->get('Authorization', ''), 7);

        if ($this->isJwt($token)) {
            return $this->jwtAuthenticator->onAuthenticationFailure($request, $exception);
        }

        return new JsonResponse(['error' => 'Invalid API token'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    private function isJwt(string $token): bool
    {
        return str_contains($token, '.');
    }

    private function isJwtPassport(Passport $passport): bool
    {
        $attributes = $passport->getAttributes();

        return array_key_exists('payload', $attributes) && array_key_exists('token', $attributes);
    }
}
