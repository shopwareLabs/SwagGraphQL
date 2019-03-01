<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\StatsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregation;
use SwagGraphQL\Resolver\Struct\AggregationStruct;

class AggregationStructTest extends TestCase
{
    public function testFromAggregationResultSimpleAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new AggregationResult(new AvgAggregation('field', 'name'), [['key' => null, 'avg' => 14]])
        );

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(1, $aggregation->getBuckets());
        $bucket = $aggregation->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(1, $bucket->getResults());
        $result = $bucket->getResults()[0];
        static::assertEquals('avg', $result->getType());
        static::assertEquals(14, $result->getResult());
    }

    public function testFromAggregationResultStatsAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new AggregationResult(
                new StatsAggregation('field', 'name'), [
                    [
                        'key' => null,
                        'count' => 1,
                        'avg' => 2.0,
                        'sum' => 3.0,
                        'min' => 4.0,
                        'max' => 5.0
                    ]
                ]
            ));

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(1, $aggregation->getBuckets());
        $bucket = $aggregation->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(5, $bucket->getResults());
        static::assertEquals('count', $bucket->getResults()[0]->getType());
        static::assertEquals(1, $bucket->getResults()[0]->getResult());
        static::assertEquals('avg', $bucket->getResults()[1]->getType());
        static::assertEquals(2.0, $bucket->getResults()[1]->getResult());
        static::assertEquals('sum', $bucket->getResults()[2]->getType());
        static::assertEquals(3.0, $bucket->getResults()[2]->getResult());
        static::assertEquals('min', $bucket->getResults()[3]->getType());
        static::assertEquals(4.0, $bucket->getResults()[3]->getResult());
        static::assertEquals('max', $bucket->getResults()[4]->getType());
        static::assertEquals(5.0, $bucket->getResults()[4]->getResult());
    }

    public function testFromAggregationResultValueCountAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new AggregationResult(
                new ValueCountAggregation('field', 'name'), [
                    [
                        'key' => null,
                        'values' => [
                            'test' => 1,
                            'another' => 2
                        ]
                    ]
                ]
            ));

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(1, $aggregation->getBuckets());
        $bucket = $aggregation->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(2, $bucket->getResults());
        static::assertEquals('test', $bucket->getResults()[0]->getType());
        static::assertEquals(1, $bucket->getResults()[0]->getResult());
        static::assertEquals('another', $bucket->getResults()[1]->getType());
        static::assertEquals(2, $bucket->getResults()[1]->getResult());
    }

    public function testFromAggregationResultValueAggregation()
    {
        $aggregation = AggregationStruct::fromAggregationResult(
            new AggregationResult(
                new ValueAggregation('field', 'name'), [
                    [
                        'key' => null,
                        'values' => [
                            'test',
                            'another'
                        ]
                    ]
                ]
            ));

        static::assertEquals('name', $aggregation->getName());
        static::assertCount(1, $aggregation->getBuckets());
        $bucket = $aggregation->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(2, $bucket->getResults());
        static::assertEquals('0', $bucket->getResults()[0]->getType());
        static::assertEquals('test', $bucket->getResults()[0]->getResult());
        static::assertEquals('1', $bucket->getResults()[1]->getType());
        static::assertEquals('another', $bucket->getResults()[1]->getResult());
    }

    public function testFromAggregationResultCollection()
    {
        $aggregations = AggregationStruct::fromCollection(new AggregationResultCollection([
            new AggregationResult(new MaxAggregation('field', 'max'), [['key' => null, 'max' => 20]]),
            new AggregationResult(new AvgAggregation('field', 'avg'), [['key' => null, 'avg' => 14]])
        ]));

        static::assertCount(2, $aggregations);
        static::assertEquals('max', $aggregations[0]->getName());
        static::assertCount(1, $aggregations[0]->getBuckets());
        $bucket = $aggregations[0]->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(1, $bucket->getResults());
        static::assertEquals('max', $bucket->getResults()[0]->getType());
        static::assertEquals(20, $bucket->getResults()[0]->getResult());

        static::assertEquals('avg', $aggregations[1]->getName());
        static::assertCount(1, $aggregations[1]->getBuckets());
        $bucket = $aggregations[1]->getBuckets()[0];
        static::assertEquals([], $bucket->getKeys());
        static::assertCount(1, $bucket->getResults());
        static::assertEquals('avg', $bucket->getResults()[0]->getType());
        static::assertEquals(14, $bucket->getResults()[0]->getResult());
    }

}