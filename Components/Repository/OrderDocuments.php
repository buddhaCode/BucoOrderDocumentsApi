<?php

namespace BucoOrderDocumentsApi\Components\Repository;

use Doctrine\ORM\QueryBuilder;
use Shopware\Components\Model\ModelRepository;

class OrderDocuments extends ModelRepository
{
    public function createQueryBuilder($alias, $indexBy = null) : QueryBuilder
    {
        $builder = parent::createQueryBuilder($alias, $indexBy)
            ->select([$alias, 'attribute'])
            ->leftJoin("{$alias}.attribute", 'attribute');

        return $builder;
    }
}