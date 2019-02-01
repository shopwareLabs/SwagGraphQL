<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver;

use Doctrine\Common\Inflector\Inflector;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagGraphQL\Resolver\Struct\ConnectionStruct;
use SwagGraphQL\Resolver\Struct\EdgeStruct;
use SwagGraphQL\Resolver\Struct\PageInfoStruct;
use SwagGraphQL\Schema\Mutation;
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
        $path = $info->path[0];
        if (is_array($path)) {
            $path = $path[0];
        }

        try {
            if (strpos($path, '__') === 0) {
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
            $entityName = Inflector::singularize($info->fieldName);
            $definition = $this->definitionRegistry->get(Inflector::tableize($entityName));
            $repo = $this->getRepository($definition);

            $criteria = CriteriaParser::buildCriteria($args, $definition);
            AssociationResolver::addAssociations($criteria, $info->getFieldSelection(PHP_INT_MAX), $definition);

            $searchResult = $repo->search($criteria, $context);

            if ($entityName !== $info->fieldName) {
                return ConnectionStruct::fromResult($searchResult);
            } else {
                return $searchResult->getEntities()->first();
            }
        }

        return $this->getSimpleValue($rootValue, $info);
    }

    /**
     * Resolver for Mutation queries
     * On the Root-Level it checks the action and calls the according function
     * On non Root-Level it returns the get-Value of the Field
     */
    private function resolveMutation($rootValue, $args, $context, ResolveInfo $info)
    {
        if ($rootValue === null) {
            $mutation = Mutation::fromName($info->fieldName);

            switch ($mutation->getAction()) {
                case Mutation::ACTION_CREATE:
                    return $this->create($args, $context, $info, $mutation->getEntityName());
                case Mutation::ACTION_UPDATE:
                    return $this->update($args, $context, $info, $mutation->getEntityName());
                case Mutation::ACTION_DELETE:
                    return $this->delete($args, $context, $mutation->getEntityName());
            }
        }

        return $this->getSimpleValue($rootValue, $info);
    }

    /**
     * Creates and returns the entity
     */
    private function create($args, $context, ResolveInfo $info, string $entity): Entity
    {
        $definition = $this->definitionRegistry->get($entity);
        $repo = $this->getRepository($definition);

        $event = $repo->create([$args], $context);
        $id = $event->getEventByDefinition($definition)->getIds()[0];

        $criteria = new Criteria([$id]);
        AssociationResolver::addAssociations($criteria, $info->getFieldSelection(PHP_INT_MAX), $definition);

        return $repo->search($criteria, $context)->get($id);
    }

    /**
     * Update and returns the entity
     */
    private function update($args, $context, ResolveInfo $info, string $entity): Entity
    {
        $definition = $this->definitionRegistry->get($entity);
        $repo = $this->getRepository($definition);

        $event = $repo->update([$args], $context);
        $id = $event->getEventByDefinition($definition)->getIds()[0];

        $criteria = new Criteria([$id]);
        AssociationResolver::addAssociations($criteria, $info->getFieldSelection(PHP_INT_MAX), $definition);

        return $repo->search($criteria, $context)->get($id);
    }

    /**
     * Deletes the entity and returns its ID
     */
    private function delete($args, $context, string $entity): string
    {
        $definition = $this->definitionRegistry->get($entity);
        $repo = $this->getRepository($definition);

        $event = $repo->delete([$args], $context);
        $id = $event->getEventByDefinition($definition)->getIds()[0];

        return $id;
    }

    private function getRepository(string $definition): EntityRepositoryInterface
    {
        $repositoryClass = $definition::getEntityName() . '.repository';

        if ($this->container->has($repositoryClass) === false) {
            throw new \Exception('Repository not found: ' . $definition::getEntityName());
        }

        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get($definition::getEntityName() . '.repository');

        return $repo;
    }

    private function wrapConnectionType(array $elements): ConnectionStruct
    {
        return (new ConnectionStruct())->assign([
            'edges' => EdgeStruct::fromElements($elements, 0),
            'total' => 0,
            'pageInfo' => new PageInfoStruct()
        ]);
    }

    private function getSimpleValue($rootValue, ResolveInfo $info)
    {
        $result = null;
        $getter = 'get' . ucfirst($info->fieldName);
        if (method_exists($rootValue, $getter)) {
            $result = $rootValue->$getter();
        }
        if (is_array($rootValue) && array_key_exists($info->fieldName, $rootValue)) {
            $result = $rootValue[$info->fieldName];
        }

        if ($result instanceof EntityCollection) {
            // ToDo handle args in connections
            return $this->wrapConnectionType($result->getElements());
        }

        return $result;
    }

}