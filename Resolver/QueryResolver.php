<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\SearchRequestException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Parser\AggregationParser;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Parser\QueryStringParser;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagGraphQL\Resolver\Struct\AggregationStruct;
use SwagGraphQL\Resolver\Struct\ConnectionStruct;
use SwagGraphQL\Resolver\Struct\EdgeStruct;
use SwagGraphQL\Resolver\Struct\PageInfoStruct;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QueryResolver
{
    /** @var ContainerInterface  */
    private $container;

    /** @var DefinitionRegistry  */
    private $definitionRegistry;

    public function __construct(ContainerInterface $container, DefinitionRegistry $definitionRegistry)
    {
        $this->container = $container;
        $this->definitionRegistry = $definitionRegistry;
    }

    /**
     * Default Resolver
     * uses the library provided defaultResolver for meta Fields
     * and the resolveQuery() and resolveMutation() function for Query and Mutation Fields
     */
    public function resolve($rootValue, $args, $context, ResolveInfo $info)
    {
        try {
            if (strpos($info->path[0], '__') === 0) {
                return Executor::defaultFieldResolver($rootValue, $args, $context, $info);
            }
            if ($info->operation->operation !== 'mutation') {
                return $this->resolveQuery($rootValue, $args, $context, $info);
            }

            return $this->resolveMutation($rootValue, $args, $context, $info);
        } catch (\Throwable $e) {
            // default error-handler will just show "internal server error"
            // therefore throw own Exception
            throw new QueryResolvingException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolver for Query queries
     * On the Root-Level it searches for the Entity with th given Args
     * On non Root-Level it returns the get-Value of the Field
     */
    private function resolveQuery($rootValue, $args, $context, ResolveInfo $info)
    {
        if ($rootValue === null) {
            $definition = $this->definitionRegistry->get($info->fieldName);
            $repo = $this->getRepository($definition);

            $criteria = CriteriaParser::buildCriteria($args, $definition);
            AssociationResolver::addAssociations($criteria, $info->getFieldSelection(PHP_INT_MAX), $definition);

            $searchResult = $repo->search($criteria, $context);

            return ConnectionStruct::fromResult($searchResult);
        }

        $getter = 'get' . ucfirst($info->fieldName);
        $result = $rootValue->$getter();

        if ($result instanceof EntityCollection) {
            // ToDo handle args in connections
            $this->wrapConnectionType($result->getElements());
            return $this->wrapConnectionType($result->getElements());
        }

        return $result;
    }

    /**
     * Resolver for Mutation queries
     * On the Root-Level it upserts an Entity and returns it
     * On non Root-Level it returns the get-Value of the Field
     */
    private function resolveMutation($rootValue, $args, $context, ResolveInfo $info)
    {
        if ($rootValue === null) {
            $definition = $this->definitionRegistry->get($this->getEntityNameFromMutation($info->fieldName));
            $repo = $this->getRepository($definition);

            $event = $repo->upsert([$args], $context);
            $id = $event->getEventByDefinition($definition)->getIds()[0];

            $criteria = new ReadCriteria([$id]);
            AssociationResolver::addAssociations($criteria, $info->getFieldSelection(PHP_INT_MAX), $definition);

            return $repo->read($criteria, $context)->get($id);
        }

        $getter = 'get' . ucfirst($info->fieldName);
        return $rootValue->$getter();
    }

    private function getRepository(string $definition): RepositoryInterface
    {
        $repositoryClass = $definition::getEntityName() . '.repository';

        if ($this->container->has($repositoryClass) === false) {
            throw new \Exception('Repository not found: ' . $definition::getEntityName());
        }

        /** @var RepositoryInterface $repo */
        $repo = $this->container->get($definition::getEntityName() . '.repository');

        return $repo;
    }

    private function getEntityNameFromMutation(string $fieldName): string
    {
        if (strpos($fieldName, 'upsert_' !== 1)) {
            throw new \Exception('Mutation without "upsert_" prefix called, got: ' . $fieldName);
        }

        return substr($fieldName, 7);
    }

    private function wrapConnectionType(array $elements): ConnectionStruct
    {
        return (new ConnectionStruct())->assign([
            'edges' => EdgeStruct::fromElements($elements, 0),
            'total' => 0,
            'pageInfo' => new PageInfoStruct()
        ]);
    }
}