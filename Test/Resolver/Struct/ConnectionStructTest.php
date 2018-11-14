<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\AvgAggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\MaxAggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use SwagGraphQL\Resolver\Struct\ConnectionStruct;

class ConnectionStructTest extends TestCase
{
    public function testFromResult()
    {
        $entity1 = (new Entity())->assign(['id' => '1']);
        $entity2 = (new Entity())->assign(['id' => '2']);
        $criteria = new Criteria;
        $criteria->setLimit(10);
        $criteria->setOffset(5);

        $result = new EntitySearchResult(
            100,
            new EntityCollection([$entity1, $entity2]),
            new AggregationResultCollection([
                new MaxAggregationResult(new MaxAggregation('field', 'max'), 20),
                new AvgAggregationResult(new AvgAggregation('field', 'avg'), 14)
            ]),
            $criteria,
            Context::createDefaultContext(Defaults::TENANT_ID)
        );
        $connection = ConnectionStruct::fromResult($result);

        static::assertEquals(100, $connection->getTotal());

        static::assertCount(2, $connection->getEdges());
        static::assertEquals($entity1, $connection->getEdges()[0]->getNode());
        static::assertEquals(base64_encode('6'), $connection->getEdges()[0]->getCursor());
        static::assertEquals($entity2, $connection->getEdges()[1]->getNode());
        static::assertEquals(base64_encode('7'), $connection->getEdges()[1]->getCursor());

        static::assertCount(2, $connection->getAggregations());
        static::assertEquals('max', $connection->getAggregations()[0]->getName());
        static::assertCount(1, $connection->getAggregations()[0]->getResults());
        static::assertEquals('max', $connection->getAggregations()[0]->getResults()[0]->getType());
        static::assertEquals(20, $connection->getAggregations()[0]->getResults()[0]->getResult());
        static::assertEquals('avg', $connection->getAggregations()[1]->getName());
        static::assertCount(1, $connection->getAggregations()[1]->getResults());
        static::assertEquals('avg', $connection->getAggregations()[1]->getResults()[0]->getType());
        static::assertEquals(14, $connection->getAggregations()[1]->getResults()[0]->getResult());

        static::assertTrue($connection->getPageInfo()->getHasNextPage());
        static::assertEquals(base64_encode('15'), $connection->getPageInfo()->getEndCursor());
        static::assertTrue($connection->getPageInfo()->getHasPreviousPage());
        static::assertEquals(base64_encode('6'), $connection->getPageInfo()->getStartCursor());
    }
}