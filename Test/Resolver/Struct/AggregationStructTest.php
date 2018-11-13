<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\StatsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\StatsAggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregationResult;
use SwagGraphQL\Resolver\Struct\AggregationStruct;

class AggregationStructTest extends TestCase
{
    public function testFromAggregationResultSimpleAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new AvgAggregationResult(new AvgAggregation('field', 'name'), 14)
        );

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(1, $aggregation->getResults());
        static::assertEquals('avg', $aggregation->getResults()[0]->getType());
        static::assertEquals(14, $aggregation->getResults()[0]->getResult());
    }

    public function testFromAggregationResultStatsAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new StatsAggregationResult(
                new StatsAggregation('field', 'name'),
                1,
                2.0,
                3.0,
                4.0,
                5.0
            ));

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(5, $aggregation->getResults());
        static::assertEquals('count', $aggregation->getResults()[0]->getType());
        static::assertEquals(1, $aggregation->getResults()[0]->getResult());
        static::assertEquals('avg', $aggregation->getResults()[1]->getType());
        static::assertEquals(2.0, $aggregation->getResults()[1]->getResult());
        static::assertEquals('min', $aggregation->getResults()[2]->getType());
        static::assertEquals(4.0, $aggregation->getResults()[2]->getResult());
        static::assertEquals('max', $aggregation->getResults()[3]->getType());
        static::assertEquals(5.0, $aggregation->getResults()[3]->getResult());
        static::assertEquals('sum', $aggregation->getResults()[4]->getType());
        static::assertEquals(3.0, $aggregation->getResults()[4]->getResult());
    }

    public function testFromAggregationResultValueCountAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new ValueCountAggregationResult(
                new ValueCountAggregation('field', 'name'),
                [
                    'test' => 1,
                    'another' => 2
                ]
            ));

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(2, $aggregation->getResults());
        static::assertEquals('test', $aggregation->getResults()[0]->getType());
        static::assertEquals(1, $aggregation->getResults()[0]->getResult());
        static::assertEquals('another', $aggregation->getResults()[1]->getType());
        static::assertEquals(2, $aggregation->getResults()[1]->getResult());
    }

    public function testFromAggregationResultCollection()
    {
        $aggregations = AggregationStruct::fromCollection(new AggregationResultCollection([
            new MaxAggregationResult(new MaxAggregation('field', 'max'), 20),
            new AvgAggregationResult(new AvgAggregation('field', 'avg'), 14)
        ]));

        static::assertCount(2, $aggregations);
        static::assertEquals('max', $aggregations[0]->getName());
        static::assertCount(1, $aggregations[0]->getResults());
        static::assertEquals('max', $aggregations[0]->getResults()[0]->getType());
        static::assertEquals(20, $aggregations[0]->getResults()[0]->getResult());

        static::assertEquals('avg', $aggregations[1]->getName());
        static::assertCount(1, $aggregations[1]->getResults());
        static::assertEquals('avg', $aggregations[1]->getResults()[0]->getType());
        static::assertEquals(14, $aggregations[1]->getResults()[0]->getResult());
    }

}