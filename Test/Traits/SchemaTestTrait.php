<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Traits;


use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

trait SchemaTestTrait
{
    private function assertObject(array $expectedFields, ObjectType $object): void
    {
        foreach ($expectedFields as $field => $type) {
            static::assertInstanceOf($type, $object->getField($field)->getType());
        }
    }

    private function assertInputArgs(array $expectedFields, FieldDefinition $object): void
    {
        foreach ($expectedFields as $field => $type) {
            static::assertInstanceOf($type, $object->getArg($field)->getType());
        }
    }

    private function assertConnectionObject(array $expectedFields, ObjectType $object): void
    {
        static::assertInstanceOf(IntType::class, $object->getField('total')->getType());
        static::assertInstanceOf(ObjectType::class, $object->getField('pageInfo')->getType());
        $this->assertPageInfo($object->getField('pageInfo')->getType());

        static::assertInstanceOf(ListOfType::class, $object->getField('aggregations')->getType());
        static::assertInstanceOf(ObjectType::class, $object->getField('aggregations')->getType()->getWrappedType());
        $this->assertAggregations($object->getField('aggregations')->getType()->getWrappedType());

        static::assertInstanceOf(ListOfType::class, $object->getField('edges')->getType());
        static::assertInstanceOf(ObjectType::class, $object->getField('edges')->getType()->getWrappedType());
        $this->assertEdges($expectedFields, $object->getField('edges')->getType()->getWrappedType());
    }

    private function assertPageInfo(ObjectType $object): void
    {
        static::assertEquals('pageInfo', $object->name);
        static::assertInstanceOf(IDType::class, $object->getField('endCursor')->getType());
        static::assertInstanceOf(BooleanType::class, $object->getField('hasNextPage')->getType());
    }

    private function assertAggregations(ObjectType $object): void
    {
        static::assertEquals('aggregationResults', $object->name);
        static::assertInstanceOf(StringType::class, $object->getField('name')->getType());
        static::assertInstanceOf(ListOfType::class, $object->getField('results')->getType());
        static::assertInstanceOf(ObjectType::class, $object->getField('results')->getType()->getWrappedType());
        $this->assertAggregationResult($object->getField('results')->getType()->getWrappedType());
    }

    private function assertAggregationResult(ObjectType $object): void
    {
        static::assertEquals('aggregationResult', $object->name);
        static::assertInstanceOf(StringType::class, $object->getField('type')->getType());
        static::assertInstanceOf(FloatType::class, $object->getField('result')->getType());
    }

    private function assertEdges(array $expectedFields, ObjectType $object): void
    {
        static::assertInstanceOf(IDType::class, $object->getField('cursor')->getType());
        static::assertInstanceOf(ObjectType::class, $object->getField('node')->getType());
        $this->assertObject($expectedFields,  $object->getField('node')->getType());
    }

    private function assertConnectionArgs(FieldDefinition $field): void
    {
        static::assertInstanceOf(IntType::class, $field->getArg('first')->getType());
        static::assertInstanceOf(StringType::class, $field->getArg('after')->getType());

        static::assertInstanceOf(StringType::class, $field->getArg('sortBy')->getType());
        $this->assertEnum([FieldSorting::ASCENDING, FieldSorting::DESCENDING], $field->getArg('sortDirection')->getType());

        static::assertInstanceOf(InputObjectType::class, $field->getArg('query')->getType());
        $this->assertQueryType($field->getArg('query')->getType());

        static::assertInstanceOf(ListOfType::class, $field->getArg('aggregations')->getType());
        static::assertInstanceOf(InputObjectType::class, $field->getArg('aggregations')->getType()->getWrappedType());
        $this->assertAggregation($field->getArg('aggregations')->getType()->getWrappedType());
    }

    private function assertQueryType(InputObjectType $object): void
    {
        static::assertEquals('query', $object->name);
        static::assertInstanceOf(StringType::class, $object->getField('field')->getType());
        static::assertInstanceOf(StringType::class, $object->getField('value')->getType());

        static::assertInstanceOf(NonNull::class, $object->getField('type')->getType());
        $this->assertEnum(['equals', 'equalsAny', 'contains', 'multi', 'not', 'range'], $object->getField('type')->getType()->getWrappedType());

        $this->assertEnum([MultiFilter::CONNECTION_AND, MultiFilter::CONNECTION_OR], $object->getField('operator')->getType());

        static::assertInstanceOf(ListOfType::class, $object->getField('queries')->getType());
        static::assertInstanceOf(InputObjectType::class, $object->getField('queries')->getType()->getWrappedType());
        static::assertEquals('query', $object->getField('queries')->getType()->getWrappedType()->name);

        static::assertInstanceOf(ListOfType::class, $object->getField('parameters')->getType());
        static::assertInstanceOf(InputObjectType::class, $object->getField('parameters')->getType()->getWrappedType());
        $this->assertQueryParameters($object->getField('parameters')->getType()->getWrappedType());
    }

    private function assertQueryParameters(InputObjectType $object): void
    {
        static::assertEquals('parameter', $object->name);
        static::assertInstanceOf(NonNull::class, $object->getField('operator')->getType());
        static::assertInstanceOf(FloatType::class, $object->getField('value')->getType()->getWrappedType());
        static::assertInstanceOf(NonNull::class, $object->getField('operator')->getType());
        $this->assertEnum(['GT', 'GTE', 'LT', 'LTE'], $object->getField('operator')->getType()->getWrappedType());
    }

    private function assertAggregation(InputObjectType $object): void
    {
        static::assertEquals('aggregation', $object->name);
        static::assertInstanceOf(NonNull::class, $object->getField('name')->getType());
        static::assertInstanceOf(StringType::class, $object->getField('name')->getType()->getWrappedType());
        static::assertInstanceOf(NonNull::class, $object->getField('field')->getType());
        static::assertInstanceOf(StringType::class, $object->getField('field')->getType()->getWrappedType());
        static::assertInstanceOf(NonNull::class, $object->getField('type')->getType());
        $this->assertEnum(['avg', 'cardinality', 'count', 'max', 'min', 'stats', 'sum', 'value_count'], $object->getField('type')->getType()->getWrappedType());
    }

    private function assertEnum(array $expectedValues, EnumType $enum): void
    {
        static::assertCount(count($expectedValues), $enum->getValues());

        foreach ($expectedValues as $value) {
            static::assertInstanceOf(EnumValueDefinition::class, $enum->getValue($value));
        }

    }
}