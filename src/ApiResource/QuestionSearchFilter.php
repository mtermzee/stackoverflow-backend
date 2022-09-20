<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class QuestionSearchFilter extends AbstractFilter
{

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.title LIKE :search OR %s.question LIKE :search', $alias, $alias))
            ->setParameter('search', '%' . $value . '%');
    }

    public function getDescription(string $resourceClass): array
    {
        //dd($this->properties);
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'openapi' => [
                    'description' => 'Search for a question',
                    'name' => 'search',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }
}
