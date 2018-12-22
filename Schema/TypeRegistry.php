<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
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
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;

class TypeRegistry
{
    /** @var ObjectType[] */
    private $types = [];

    /** @var InputObjectType[] */
    private $inputTypes = [];

    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var CustomTypes */
    private $customTypes;

    public function __construct(DefinitionRegistry $definitionRegistry, CustomTypes $customTypes)
    {
        $this->definitionRegistry = $definitionRegistry;
        $this->customTypes = $customTypes;
    }

    public function getQuery(): ObjectType
    {
        $fields = [];
        foreach ($this->definitionRegistry->getElements() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }

            $fields[$definition::getEntityName()]['args'] = $this->getConnectionArgs();
            $fields[$definition::getEntityName()]['type'] = $this->getConnectionTypeForDefinition($definition::getEntityName());
        }

        return new ObjectType([
            'name' => 'Query',
            'fields' => $fields
        ]);
    }

    public function getMutation(): ObjectType
    {
        $fields = [];
        foreach ($this->definitionRegistry->getElements() as $definition) {
            if ($this->isTranslationDefinition($definition) || $this->isMappingDefinition($definition)) {
                continue;
            }
            $upsertName = new Mutation(Mutation::ACTION_UPSERT, $definition::getEntityName());
            $fields[$upsertName->getName()]['args'] = $this->getInputFieldsForDefinition($definition::getEntityName());
            $fields[$upsertName->getName()]['type'] = $this->getObjectForDefinition($definition::getEntityName());

            $deleteName = new Mutation(Mutation::ACTION_DELETE, $definition::getEntityName());
            $fields[$deleteName->getName()]['args'] = $this->getPrimaryKeyFields($definition::getEntityName());
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

    private function getObjectForDefinition(string $name): ObjectType
    {
        if (!isset($this->types[$name])) {
            $this->types[$name] = new ObjectType([
                'name' => $name,
                'fields' => function () use ($name) {
                    return $this->getFieldsForDefinition($name);
                }
            ]);
        }

        return $this->types[$name];
    }

    private function getInputForDefinition(string $name): InputObjectType
    {
        if (!isset($this->inputTypes[$name])) {
            $this->inputTypes[$name] = new InputObjectType([
                'name' => 'input_' . $name,
                'fields' => function () use ($name) {
                    return $this->getInputFieldsForDefinition($name);
                }
            ]);
        }

        return $this->inputTypes[$name];
    }

    private function getConnectionTypeForDefinition(string $name): ObjectType
    {
        $edge = $this->getEdgeTypeForDefinition($name);

        if (!isset($this->types[$name . '_connection'])) {
            $this->types[$name . '_connection'] = new ObjectType([
                'name' => $name . '_connection',
                'fields' => [
                    'total' => Type::int(),
                    'edges' => $edge,
                    'pageInfo' => $this->customTypes->pageInfo(),
                    'aggregations' => Type::listOf($this->customTypes->aggregationResult())
                ]
            ]);
        }

        return $this->types[$name . '_connection'];
    }

    private function getEdgeTypeForDefinition(string $name): ListOfType
    {
        if (!isset($this->types[$name . '_edge'])) {
            $this->types[$name . '_edge'] = Type::listOf(new ObjectType([
                'name' => $name . '_edge',
                'fields' => [
                    'node' => $this->getObjectForDefinition($name),
                    'cursor' => Type::id()
                ]
            ]));
        }

        return $this->types[$name . '_edge'];
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

    private function getFieldsForDefinition(string $name): array
    {
        $definition = $this->definitionRegistry->get($name);

        $fields = [];
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field);
            if ($type) {
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getPrimaryKeyFields(string $name): array
    {
        $definition = $this->definitionRegistry->get($name);

        $fields = [];
        foreach ($definition::getFields()->filterByFlag(PrimaryKey::class) as $field) {
            $type = $this->getFieldType($field, true);
            if ($type) {
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getInputFieldsForDefinition(string $name): array
    {
        $definition = $this->definitionRegistry->get($name);

        $fields = [];
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field, true);
            if ($type) {
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        $fields = $this->getDefaults($definition, $fields);

        return $fields;
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
                    Type::listOf($this->getInputForDefinition($field->getReferenceDefinition()::getEntityName())) :
                    $this->getConnectionTypeForDefinition($field->getReferenceDefinition()::getEntityName());
                break;
            case $field instanceof OneToManyAssociationField:
                $type = $type = $input ?
                    Type::listOf($this->getInputForDefinition($field->getReferenceClass()::getEntityName())) :
                    $this->getConnectionTypeForDefinition($field->getReferenceClass()::getEntityName());
                break;
            case $field instanceof ManyToOneAssociationField:
                $type = $input ?
                    $this->getInputForDefinition($field->getReferenceClass()::getEntityName()) :
                    $this->getObjectForDefinition($field->getReferenceClass()::getEntityName());
                break;
            default:
                // StructField, StructCollectionField, TranslationAssociationField are not exposed
                return null;
        }

        if (!$input && $field->getFlag(Required::class)) {
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
}