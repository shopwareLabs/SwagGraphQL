<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagGraphQL\Resolver\Struct\PageInfoStruct;

class PageInfoStructTest extends TestCase
{
    public function testFromCriteria()
    {
        $criteria = new Criteria;
        $criteria->setLimit(10);
        $criteria->setOffset(5);

        $pageInfo = PageInfoStruct::fromCriteria($criteria, 100);

        static::assertTrue($pageInfo->getHasNextPage());
        static::assertTrue($pageInfo->getHasPreviousPage());
        static::assertEquals(base64_encode('15'), $pageInfo->getEndCursor());
        static::assertEquals(base64_encode('6'), $pageInfo->getStartCursor());
    }
}