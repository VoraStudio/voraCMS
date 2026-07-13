<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\JwtDomainValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtClientIdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly JwtDomainValidator $domainValidator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJwtCreated',
            Events::JWT_DECODED => 'onJwtDecoded',
        ];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            $payload = $event->getData();
            $payload['user_id'] = $user->getId();
            $payload['user_slug'] = $user->getSlug();
            $payload['allowed_domains'] = $user->getAllowedDomains() ?? [];

            $event->setData($payload);
        }
    }

    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        if (!$this->domainValidator->validateFromPayload($event->getPayload())) {
            $event->markAsInvalid();
        }
    }
}
