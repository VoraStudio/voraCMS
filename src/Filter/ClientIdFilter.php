<?php

namespace App\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ClientIdFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('client_id')) {
            return '';
        }

        try {
            $clientId = $this->getParameter('client_id');
        } catch (\InvalidArgumentException) {
            return '';
        }

        if ($clientId === null || $clientId === '') {
            return '';
        }

        $connection = $this->getConnection();
        $clientId = $connection->quote($clientId);

        return sprintf('%s.client_id = %s', $targetTableAlias, $clientId);
    }
}
