<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use PHPUnit\Framework\TestCase;
use SwagGraphQL\Schema\CustomTypes;
use SwagGraphQL\Types\DateType;
use SwagGraphQL\Types\JsonType;

class CustomTypesTest extends TestCase
{
    public function testDate()
    {
        static::assertInstanceOf(DateType::class, CustomTypes::date());
        CustomTypes::date()->assertValid();
    }

    public function testJson()
    {
        static::assertInstanceOf(JsonType::class, CustomTypes::json());
        CustomTypes::json()->assertValid();
    }

    public function testSortDirection()
    {
        static::assertInstanceOf(EnumType::class, CustomTypes::sortDirection());
        CustomTypes::sortDirection()->assertValid();
    }

    public function testQueryOperator()
    {
        static::assertInstanceOf(EnumType::class, CustomTypes::queryOperator());
        CustomTypes::queryOperator()->assertValid();
    }

    public function testRangeOperator()
    {
        static::assertInstanceOf(EnumType::class, CustomTypes::rangeOperator());
        CustomTypes::rangeOperator()->assertValid();
    }

    public function testQueryTypes()
    {
        static::assertInstanceOf(EnumType::class, CustomTypes::queryTypes());
        CustomTypes::queryTypes()->assertValid();
    }

    public function testAggregationTypes()
    {
        static::assertInstanceOf(EnumType::class, CustomTypes::aggregationTypes());
        CustomTypes::aggregationTypes()->assertValid();
    }

    public function testPageInfo()
    {
        static::assertInstanceOf(ObjectType::class, CustomTypes::pageInfo());
        CustomTypes::pageInfo()->assertValid();
    }

    public function testAggregationResult()
    {
        static::assertInstanceOf(ObjectType::class, CustomTypes::aggregationResult());
        CustomTypes::aggregationResult()->assertValid();
    }

    public function testQuery()
    {
        static::assertInstanceOf(InputObjectType::class, CustomTypes::query());
        CustomTypes::query()->assertValid();
    }

    public function testAggregation()
    {
        static::assertInstanceOf(InputObjectType::class, CustomTypes::aggregation());
        CustomTypes::aggregation()->assertValid();
    }
}