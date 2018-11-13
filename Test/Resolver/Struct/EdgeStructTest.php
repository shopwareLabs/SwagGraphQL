<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Resolver\Struct;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagGraphQL\Resolver\Struct\EdgeStruct;

class EdgeStructTest extends TestCase
{
    public function testFromElements()
    {
        $entity1 = (new Entity())->assign(['id' => 1]);
        $entity2 = (new Entity())->assign(['id' => 2]);

        $edges = EdgeStruct::fromElements(
            [
                $entity1,
                $entity2
            ],
            10
        );

        static::assertCount(2, $edges);

        static::assertEquals($entity1, $edges[0]->getNode());
        static::assertEquals(base64_encode('11'), $edges[0]->getCursor());

        static::assertEquals($entity2, $edges[1]->getNode());
        static::assertEquals(base64_encode('12'), $edges[1]->getCursor());
    }
}