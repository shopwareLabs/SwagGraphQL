<?php declare(strict_types=1);

namespace SwagGraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use SwagGraphQL\Types\DateType;
use SwagGraphQL\Types\JsonType;

class TypeRegistry
{
    /** @var ObjectType[] */
    private $types = [];

    /** @var InputObjectType[] */
    private $inputTypes = [];

    /** @var DefinitionRegistry */
    private $definitionRegistry;

    public function __construct(DefinitionRegistry $definitionRegistry)
    {
        $this->definitionRegistry = $definitionRegistry;
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
            $fields['upsert_' . $definition::getEntityName()]['args'] = $this->getInputFieldsForDefinition($definition::getEntityName());
            $fields['upsert_' . $definition::getEntityName()]['type'] = $this->getObjectForDefinition($definition::getEntityName());
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
                    'pageInfo' => CustomTypes::pageInfo(),
                    'aggregations' => Type::listOf(CustomTypes::aggregationResult())
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
            'sortDirection' => ['type' => CustomTypes::sortDirection()],
            'query' => ['type' => CustomTypes::query()],
            'aggregations' => ['type' => Type::listOf(CustomTypes::aggregation())]
        ];
    }

    private function getFieldsForDefinition(string $name): array
    {
        /** @var EntityDefinition $definition */
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

    private function getInputFieldsForDefinition(string $name): array
    {
        /** @var EntityDefinition $definition */
        $definition = $this->definitionRegistry->get($name);

        $fields = [];
        foreach ($definition::getFields() as $field) {
            $type = $this->getFieldType($field, true);
            if ($type) {
                $fields[$field->getPropertyName()]['type'] = $type;
            }
        }

        return $fields;
    }

    private function getFieldType(Field $field, bool $input = false): ?Type
    {
        $type = null;
        switch (true) {
            case $field instanceof IdField:
                $type = Type::id();
                break;
            case $field instanceof BoolField:
                $type = Type::boolean();
                break;
            case $field instanceOf DateField:
                $type = CustomTypes::date();
                break;
            case $field instanceof IntField:
                $type = Type::int();
                break;
            case $field instanceof FloatField:
                $type = Type::float();
                break;
            case $field instanceof JsonField:
                $type = CustomTypes::json();
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
                $type = $input ? $this->getInputForDefinition($field->getReferenceClass()::getEntityName()) : $this->getObjectForDefinition($field->getReferenceClass()::getEntityName());
                break;
            case $field instanceof FkField:
                $type = Type::id();
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
}