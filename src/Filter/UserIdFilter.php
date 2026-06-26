<?php

namespace App\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class UserIdFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('user_id')) {
            return '';
        }

        try {
            $userId = $this->getParameter('user_id');
        } catch (\InvalidArgumentException) {
            return '';
        }

        if ($userId === null || $userId === '') {
            return '';
        }

        $connection = $this->getConnection();
        $userId = $connection->quote($userId);

        return sprintf('%s.user_id = %s', $targetTableAlias, $userId);
    }
}
