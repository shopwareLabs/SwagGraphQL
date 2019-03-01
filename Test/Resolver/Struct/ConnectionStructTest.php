<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use SwagGraphQL\Resolver\Struct\ConnectionStruct;

class ConnectionStructTest extends TestCase
{
    public function testFromResult()
    {
        $entity1 = new ProductEntity();
        $entity1->setId('1');
        $entity2 = new ProductEntity();
        $entity2->setId('2');
        $criteria = new Criteria;
        $criteria->setLimit(10);
        $criteria->setOffset(5);

        $result = new EntitySearchResult(
            100,
            new EntityCollection([$entity1, $entity2]),
            new AggregationResultCollection([
                new AggregationResult(new MaxAggregation('field', 'max'), [['key' => null, 'max' => 20]]),
                new AggregationResult(new AvgAggregation('field', 'avg'), [['key' => null, 'avg' => 14]])
            ]),
            $criteria,
            Context::createDefaultContext()
        );
        $connection = ConnectionStruct::fromResult($result);

        static::assertEquals(100, $connection->getTotal());

        static::assertCount(2, $connection->getEdges());
        static::assertEquals($entity1, $connection->getEdges()[0]->getNode());
        static::assertEquals(base64_encode('6'), $connection->getEdges()[0]->getCursor());
        static::assertEquals($entity2, $connection->getEdges()[1]->getNode());
        static::assertEquals(base64_encode('7'), $connection->getEdges()[1]->getCursor());

        $aggregations = $connection->getAggregations();
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

        static::assertTrue($connection->getPageInfo()->getHasNextPage());
        static::assertEquals(base64_encode('15'), $connection->getPageInfo()->getEndCursor());
        static::assertTrue($connection->getPageInfo()->getHasPreviousPage());
        static::assertEquals(base64_encode('6'), $connection->getPageInfo()->getStartCursor());
    }
}