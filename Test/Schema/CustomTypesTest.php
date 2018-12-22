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
    private $customTypes;

    public function setUp()
    {
        $this->customTypes = new CustomTypes();
    }

    public function testDate()
    {
        static::assertInstanceOf(DateType::class, $this->customTypes->date());
        $this->customTypes->date()->assertValid();
    }

    public function testJson()
    {
        static::assertInstanceOf(JsonType::class, $this->customTypes->json());
        $this->customTypes->json()->assertValid();
    }

    public function testSortDirection()
    {
        static::assertInstanceOf(EnumType::class, $this->customTypes->sortDirection());
        $this->customTypes->sortDirection()->assertValid();
    }

    public function testQueryOperator()
    {
        static::assertInstanceOf(EnumType::class, $this->customTypes->queryOperator());
        $this->customTypes->queryOperator()->assertValid();
    }

    public function testRangeOperator()
    {
        static::assertInstanceOf(EnumType::class, $this->customTypes->rangeOperator());
        $this->customTypes->rangeOperator()->assertValid();
    }

    public function testQueryTypes()
    {
        static::assertInstanceOf(EnumType::class, $this->customTypes->queryTypes());
        $this->customTypes->queryTypes()->assertValid();
    }

    public function testAggregationTypes()
    {
        static::assertInstanceOf(EnumType::class, $this->customTypes->aggregationTypes());
        $this->customTypes->aggregationTypes()->assertValid();
    }

    public function testPageInfo()
    {
        static::assertInstanceOf(ObjectType::class, $this->customTypes->pageInfo());
        $this->customTypes->pageInfo()->assertValid();
    }

    public function testAggregationResult()
    {
        static::assertInstanceOf(ObjectType::class, $this->customTypes->aggregationResult());
        $this->customTypes->aggregationResult()->assertValid();
    }

    public function testQuery()
    {
        static::assertInstanceOf(InputObjectType::class, $this->customTypes->query());
        $this->customTypes->query()->assertValid();
    }

    public function testAggregation()
    {
        static::assertInstanceOf(InputObjectType::class, $this->customTypes->aggregation());
        $this->customTypes->aggregation()->assertValid();
    }
}