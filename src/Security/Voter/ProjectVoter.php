<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\UserProjectRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    public const MANAGE_CT = 'MANAGE_CT';

    public function __construct(
        private readonly UserProjectRepository $userProjectRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE_CT && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $userProject = $this->userProjectRepository->findOneByUserAndProject($user, $project);

        if (in_array('ROLE_MOD', $user->getRoles(), true)) {
            if ($userProject !== null) {
                return $userProject->canManageContentTypes();
            }
            return true;
        }

        if ($userProject !== null && $userProject->canManageContentTypes()) {
            return true;
        }

        return false;
    }
}
