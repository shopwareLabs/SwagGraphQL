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
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilderCollection;
use SwagGraphQL\Schema\SchemaBuilder\FieldBuilder;
use SwagGraphQL\Schema\SchemaBuilder\ObjectBuilder;

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
            $this->types[$definition::getEntityName()] =
                ObjectBuilder::create(Inflector::classify($definition::getEntityName()))
                ->addLazyFieldCollection(function () use ($definition) {
                    return $this->getFieldsForDefinition($definition);
                })
                ->build();
        }

        return $this->types[$definition::getEntityName()];
    }

    public function getQuery(): ObjectType
    {
        $query = ObjectBuilder::create('Query');
        foreach ($this->definitionRegistry->getDefinitions() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }

            $fieldName = Inflector::camelize($definition::getEntityName());

            $query->addField(
                FieldBuilder::create(
                    $fieldName,
                    $this->getObjectForDefinition($definition)
                )
                ->setArguments($this->getPrimaryKeyArgs($definition))
            );

            $query->addField(
                FieldBuilder::create(
                    Inflector::pluralize($fieldName),
                    $this->getConnectionTypeForDefinition($definition)
                )
                ->setArguments($this->getConnectionArgs())
            );
        }
        return $query
            ->addLazyFieldCollection(function () { return $this->customQueries(); })
            ->build();
    }

    public function getMutation(): ObjectType
    {
        $mutation = ObjectBuilder::create('Mutation');
        foreach ($this->definitionRegistry->getDefinitions() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }
            $createName = new Mutation(Mutation::ACTION_CREATE, $definition::getEntityName());
            $mutation->addField(
                FieldBuilder::create($createName->getName(), $this->getObjectForDefinition($definition))
                    ->setArguments($this->getInputFieldsForCreate($definition))
            );

            $updateName = new Mutation(Mutation::ACTION_UPDATE, $definition::getEntityName());
            $mutation->addField(
                FieldBuilder::create($updateName->getName(), $this->getObjectForDefinition($definition))
                    ->setArguments($this->getInputFieldsForUpdate($definition))
            );

            $deleteName = new Mutation(Mutation::ACTION_DELETE, $definition::getEntityName());
            $mutation->addField(
                FieldBuilder::create($deleteName->getName(), Type::id())
                    ->setArguments($this->getPrimaryKeyArgs($definition))
            );
        }

        return $mutation
            ->addLazyFieldCollection(function () { return $this->customMutations(); })
            ->build();
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
            $this->inputTypes[$definition::getEntityName()] =
                ObjectBuilder::create('Input' . Inflector::classify($definition::getEntityName()))
                ->addLazyFieldCollection(function () use ($definition) {
                    return $this->getInputFieldsForDefinition($definition);
                })
                ->buildAsInput();
        }

        return $this->inputTypes[$definition::getEntityName()];
    }

    private function getConnectionTypeForDefinition(string $definition): ObjectType
    {
        if (!isset($this->types[$definition::getEntityName() . '_connection'])) {

            $this->types[$definition::getEntityName() . '_connection'] =
                ObjectBuilder::create(Inflector::classify($definition::getEntityName()) . 'Connection')
                ->addField(FieldBuilder::create('total', Type::int())->setDescription('The total of Items found by the Query'))
                ->addField(FieldBuilder::create('edges', $this->getEdgeTypeForDefinition($definition))->setDescription('A List of the Items'))
                ->addField(FieldBuilder::create('pageInfo', $this->customTypes->pageInfo())->setDescription('Additional information for pagination'))
                ->addField(FieldBuilder::create('aggregations', Type::listOf($this->customTypes->aggregationResult()))->setDescription('the result of aggregations'))
                ->setDescription('The Result for a search that returns multiple Items')
                ->build();
        }

        return $this->types[$definition::getEntityName() . '_connection'];
    }

    private function getEdgeTypeForDefinition(string $definition): ListOfType
    {
        if (!isset($this->types[$definition::getEntityName() . '_edge'])) {
            $this->types[$definition::getEntityName() . '_edge'] = Type::listOf(
                ObjectBuilder::create(Inflector::classify($definition::getEntityName()) . 'Edge')
                ->addField(FieldBuilder::create('node', $this->getObjectForDefinition($definition))->setDescription('The Node of the Edge that contains the real element'))
                ->addField(FieldBuilder::create('cursor', Type::id())->setDescription('The cursor to the Item of the Edge'))
                ->setDescription('Contains the information for one Edge')
                ->build()
            );
        }

        return $this->types[$definition::getEntityName() . '_edge'];
    }

    private function getConnectionArgs(): FieldBuilderCollection
    {
        return FieldBuilderCollection::create()
            ->addField('first', Type::int(), 'The count of items to be returned')
            ->addField('last', Type::int(), 'The count of items to be returned')
            ->addField('after', Type::string(), 'The cursor to the first Result to be fetched')
            ->addField('before', Type::string(), 'The cursor to the last Result to be fetched')
            ->addField('sortBy', Type::string(), 'The field used for sorting')
            ->addField('sortDirection', $this->customTypes->sortDirection(), 'The direction of the sorting')
            ->addField('query', $this->customTypes->query(), 'The query the DAL should perform')
            ->addField('aggregations', Type::listOf($this->customTypes->aggregation()), 'The aggregations should perform');
    }

    private function getFieldsForDefinition(string $definition): FieldBuilderCollection
    {
        $fields = FieldBuilderCollection::create();
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field);
            if ($type) {
                $field = FieldBuilder::create($field->getPropertyName(), $type);

                if ($type->name && substr($type->name, -10) === 'Connection') {
                     $field->setArguments($this->getConnectionArgs());
                }

                $fields->addFieldBuilder($field);
            }
        }

        return $fields;
    }

    private function getPrimaryKeyArgs(string $definition): FieldBuilderCollection
    {
        $args = FieldBuilderCollection::create();
        foreach ($definition::getFields()->filterByFlag(PrimaryKey::class) as $field) {
            /** @var ObjectType|ScalarType|InputObjectType|ListOfType|null $type */
            $type = $this->getFieldType($field, true);
            if ($type) {
                if (!$field instanceof VersionField) {
                    $type = Type::nonNull($type);
                }
                $args->addField($field->getPropertyName(), $type);
            }
        }

        return $args;
    }

    private function getInputFieldsForDefinition(
        string $definition,
        \Closure $typeModifier = null,
        bool $withDefaults = false
    ): FieldBuilderCollection
    {
        $fields = FieldBuilderCollection::create();
        $defaults = $definition::getDefaults(new EntityExistence($definition, [], false, false, false, []));
        /** @var Field $field */
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field, true);
            if ($type) {
                if ($typeModifier) {
                    $type = $typeModifier($type, $field);
                }
                $builder = FieldBuilder::create($field->getPropertyName(), $type);

                if ($withDefaults && array_key_exists($field->getPropertyName(), $defaults)) {
                        $builder->setDefault($defaults[$field->getPropertyName()]);
                }
                $fields->addFieldBuilder($builder);
            }
        }

        return $fields;
    }

    private function getInputFieldsForCreate(string $definition): FieldBuilderCollection
    {
        return $this->getInputFieldsForDefinition($definition, function($type, Field $field) {
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
        }, true);
    }

    private function getInputFieldsForUpdate(string $definition): FieldBuilderCollection
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

    private function customQueries(): FieldBuilderCollection
    {
        $fields = FieldBuilderCollection::create();
        /** @var GraphQLField $query */
        foreach ($this->queries->getFields() as $name => $query) {
            $fields->addFieldBuilder(
                FieldBuilder::create($name, $query->returnType())
                ->setArguments($query->defineArgs())
                ->setDescription($query->description())
                ->setResolver(function($rootValue, $args, Context $context, ResolveInfo $info) use ($query) {
                    try {
                        return $query->resolve($rootValue, $args, $context, $info);
                    } catch (\Throwable $e) {
                        // default error-handler will just show "internal server error"
                        // therefore throw own Exception
                        throw new QueryResolvingException($e->getMessage(), 0, $e);
                    }
                })
            );
        }

        return $fields;
    }

    private function customMutations(): FieldBuilderCollection
    {
        $fields = FieldBuilderCollection::create();

        /** @var GraphQLField $mutation */
        foreach ($this->mutations->getFields() as $name => $mutation) {
            $fields->addFieldBuilder(
                FieldBuilder::create($name, $mutation->returnType())
                    ->setArguments($mutation->defineArgs())
                    ->setDescription($mutation->description())
                    ->setResolver(function($rootValue, $args, Context $context, ResolveInfo $info) use ($mutation) {
                        try {
                            return $mutation->resolve($rootValue, $args, $context, $info);
                        } catch (\Throwable $e) {
                            // default error-handler will just show "internal server error"
                            // therefore throw own Exception
                            throw new QueryResolvingException($e->getMessage(), 0, $e);
                        }
                    })
            );
        }

        return $fields;
    }
}