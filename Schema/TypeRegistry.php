<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use Doctrine\Common\Inflector\Inflector;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextWithHtmlField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use SwagGraphQL\CustomFields\GraphQLField;
use SwagGraphQL\Resolver\QueryResolvingException;

class TypeRegistry
{
    /**
     * @var array
     */
    private $types = [];

    /**
     * @var InputObjectType[]
     */
    private $inputTypes = [];

    /**
     * @var DefinitionRegistry
     */
    private $definitionRegistry;

    /**
     * @var CustomTypes
     */
    private $customTypes;

    /**
     * @var CustomFieldRegistry
     */
    private $queries;

    /**
     * @var CustomFieldRegistry
     */
    private $mutations;

    public function __construct(
        DefinitionRegistry $definitionRegistry,
        CustomTypes $customTypes,
        CustomFieldRegistry $queries,
        CustomFieldRegistry $mutations
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->customTypes = $customTypes;
        $this->queries = $queries;
        $this->mutations = $mutations;
    }

    public function getObjectForDefinition(string $definition): ObjectType
    {
        if (!isset($this->types[$definition::getEntityName()])) {
            $this->types[$definition::getEntityName()] = new ObjectType([
                'name' => Inflector::classify($definition::getEntityName()),
                'fields' => function () use ($definition) {
                    return $this->getFieldsForDefinition($definition);
                }
            ]);
        }

        return $this->types[$definition::getEntityName()];
    }

    public function getQuery(): ObjectType
    {
        $fields = $this->customQueries();
        foreach ($this->definitionRegistry->getElements() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }

            $fieldName = Inflector::camelize($definition::getEntityName());
            $fields[$fieldName]['args'] = $this->getPrimaryKeyFields($definition);
            $fields[$fieldName]['type'] = $this->getObjectForDefinition($definition);

            $pluralizedName = Inflector::pluralize($fieldName);
            $fields[$pluralizedName]['args'] = $this->getConnectionArgs();
            $fields[$pluralizedName]['type'] = $this->getConnectionTypeForDefinition($definition);
        }

        return new ObjectType([
            'name' => 'Query',
            'fields' => $fields
        ]);
    }

    public function getMutation(): ObjectType
    {
        $fields = $this->customMutations();
        foreach ($this->definitionRegistry->getElements() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }
            $createName = new Mutation(Mutation::ACTION_CREATE, $definition::getEntityName());
            $fields[$createName->getName()]['args'] = $this->getInputFieldsForCreate($definition);
            $fields[$createName->getName()]['type'] = $this->getObjectForDefinition($definition);

            $updateName = new Mutation(Mutation::ACTION_UPDATE, $definition::getEntityName());
            $fields[$updateName->getName()]['args'] = $this->getInputFieldsForUpdate($definition);
            $fields[$updateName->getName()]['type'] = $this->getObjectForDefinition($definition);

            $deleteName = new Mutation(Mutation::ACTION_DELETE, $definition::getEntityName());
            $fields[$deleteName->getName()]['args'] = $this->getPrimaryKeyFields($definition);
            $fields[$deleteName->getName()]['type'] = Type::id();
        }

        return new ObjectType([
            'name' => 'Mutation',
            'fields' => $fields
        ]);
    }

    private function isTranslationDefinition($definition): bool
    {
        return strpos($definition::getEntityName(), '_translation') !== false;
    }

    private function isMappingDefinition($definition): bool
    {
        $instance = new $definition();
        return $instance instanceof MappingEntityDefinition;
    }

    private function getInputForDefinition(string $definition): InputObjectType
    {
        if (!isset($this->inputTypes[$definition::getEntityName()])) {
            $this->inputTypes[$definition::getEntityName()] = new InputObjectType([
                'name' => 'Input' . Inflector::classify($definition::getEntityName()),
                'fields' => function () use ($definition) {
                    return $this->getInputFieldsForDefinition($definition);
                }
            ]);
        }

        return $this->inputTypes[$definition::getEntityName()];
    }

    private function getConnectionTypeForDefinition(string $definition): ObjectType
    {
        $edge = $this->getEdgeTypeForDefinition($definition);

        if (!isset($this->types[$definition::getEntityName() . '_connection'])) {
            $this->types[$definition::getEntityName() . '_connection'] = new ObjectType([
                'name' => Inflector::classify($definition::getEntityName()) . 'Connection',
                'fields' => [
                    'total' => Type::int(),
                    'edges' => $edge,
                    'pageInfo' => $this->customTypes->pageInfo(),
                    'aggregations' => Type::listOf($this->customTypes->aggregationResult())
                ]
            ]);
        }

        return $this->types[$definition::getEntityName() . '_connection'];
    }

    private function getEdgeTypeForDefinition(string $definition): ListOfType
    {
        if (!isset($this->types[$definition::getEntityName() . '_edge'])) {
            $this->types[$definition::getEntityName() . '_edge'] = Type::listOf(new ObjectType([
                'name' => Inflector::classify($definition::getEntityName()) . 'Edge',
                'fields' => [
                    'node' => $this->getObjectForDefinition($definition),
                    'cursor' => Type::id()
                ]
            ]));
        }

        return $this->types[$definition::getEntityName() . '_edge'];
    }

    private function getConnectionArgs(): array
    {
        return [
            'first' => ['type' => Type::int()],
            'last' => ['type' => Type::int()],
            'after' => ['type' => Type::string()],
            'before' => ['type' => Type::string()],
            'sortBy' => ['type' => Type::string()],
            'sortDirection' => ['type' => $this->customTypes->sortDirection()],
            'query' => ['type' => $this->customTypes->query()],
            'aggregations' => ['type' => Type::listOf($this->customTypes->aggregation())]
        ];
    }

    private function getFieldsForDefinition(string $definition): array
    {
        $fields = [];
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field);
            if ($type) {
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getPrimaryKeyFields(string $definition): array
    {
        $fields = [];
        foreach ($definition::getFields()->filterByFlag(PrimaryKey::class) as $field) {
            /** @var ObjectType|ScalarType|InputObjectType|ListOfType|null $type */
            $type = $this->getFieldType($field, true);
            if ($type) {
                if (!$field instanceof VersionField) {
                    $type = Type::nonNull($type);
                }
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getInputFieldsForDefinition(string $definition, \Closure $typeModifier = null): array
    {
        $fields = [];
        /** @var Field $field */
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field, true);
            if ($type) {
                if ($typeModifier) {
                    $type = $typeModifier($type, $field);
                }
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getInputFieldsForCreate(string $definition): array
    {
        $fields = $this->getInputFieldsForDefinition($definition, function($type, Field $field) {
            // We wrap all required Fields as NonNullable
            // Except IDs because we assume that those will be generate or come from the ID field of the association Object
            // also CreatedAt and UpdatedAt are marked as required in the DAL but they are not necessary
            if ($field->getFlag(Required::class) &&
                !$type instanceof IDType &&
                !$field instanceof UpdatedAtField &&
                !$field instanceof CreatedAtField &&
                !$field instanceof TranslationsAssociationField) {
                return Type::nonNull($type);
            }

            return $type;
        });

        return $this->getDefaults($definition, $fields);
    }

    private function getInputFieldsForUpdate(string $definition): array
    {
        return $this->getInputFieldsForDefinition($definition, function($type, Field $field) {
            // we make PKs required for Update
            if ($field->getFlag(PrimaryKey::class) && !$type instanceof NonNull && !$field instanceof VersionField) {
                return Type::nonNull($type);
            }

            return $type;
        });
    }

    private function getFieldType(Field $field, bool $input = false): ?Type
    {
        $type = null;
        switch (true) {
            case $field instanceof IdField:
            case $field instanceof FkField:
                $type = Type::id();
                break;
            case $field instanceof BoolField:
                $type = Type::boolean();
                break;
            case $field instanceOf DateField:
                $type = $this->customTypes->date();
                break;
            case $field instanceof IntField:
                $type = Type::int();
                break;
            case $field instanceof FloatField:
                $type = Type::float();
                break;
            case $field instanceof JsonField:
                $type = $this->customTypes->json();
                break;
            case $field instanceof LongTextField:
            case $field instanceof LongTextWithHtmlField:
            case $field instanceof StringField:
            case $field instanceof TranslatedField:
                $type = Type::string();
                break;
            case $field instanceof ManyToManyAssociationField:
                $type = $input ?
                    Type::listOf($this->getInputForDefinition($field->getReferenceDefinition())) :
                    $this->getConnectionTypeForDefinition($field->getReferenceDefinition());
                break;
            case $field instanceof OneToManyAssociationField:
                $type = $type = $input ?
                    Type::listOf($this->getInputForDefinition($field->getReferenceClass())) :
                    $this->getConnectionTypeForDefinition($field->getReferenceClass());
                break;
            case $field instanceof ManyToOneAssociationField:
                $type = $input ?
                    $this->getInputForDefinition($field->getReferenceClass()) :
                    $this->getObjectForDefinition($field->getReferenceClass());
                break;
            default:
                // StructField, StructCollectionField, TranslationAssociationField are not exposed
                return null;
        }

        if ((!$input) && $field->getFlag(Required::class)) {
            return Type::nonNull($type);
        }

        return $type;
    }

    private function getDefaults(string $definition, array $fields): array
    {
        $defaults = $definition::getDefaults(new EntityExistence($definition, [], false, false, false, []));
        foreach ($defaults as $propertyName => $default) {
            if (array_key_exists($propertyName, $fields)) {
                $fields[$propertyName]['defaultValue'] = $default;
            }
        }

        return $fields;
    }

    private function customQueries(): array
    {
        $fields = [];

        /** @var GraphQLField $query */
        foreach ($this->queries->getFields() as $name => $query) {
            $fields[$name] = [
                'type' => $query->returnType(),
                'args' => $query->defineArgs(),
                'description' => $query->description(),
                'resolve' => function($rootValue, $args, Context $context, ResolveInfo $info) use ($query) {
                    try {
                        return $query->resolve($rootValue, $args, $context, $info);
                    } catch (\Throwable $e) {
                        // default error-handler will just show "internal server error"
                        // therefore throw own Exception
                        throw new QueryResolvingException($e->getMessage(), 0, $e);
                    }
                }
            ];
        }

        return $fields;
    }

    private function customMutations(): array
    {
        $fields = [];

        /** @var GraphQLField $mutation */
        foreach ($this->mutations->getFields() as $name => $mutation) {
            $fields[$name] = [
                'type' => $mutation->returnType(),
                'args' => $mutation->defineArgs(),
                'description' => $mutation->description(),
                'resolve' => function($rootValue, $args, Context $context, ResolveInfo $info) use ($mutation) {
                    try {
                        return $mutation->resolve($rootValue, $args, $context, $info);
                    } catch (\Throwable $e) {
                        // default error-handler will just show "internal server error"
                        // therefore throw own Exception
                        throw new QueryResolvingException($e->getMessage(), 0, $e);
                    }
                }
            ];
        }

        return $fields;
    }
}