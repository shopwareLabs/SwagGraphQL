<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use PHPUnit\Framework\TestCase;
use SwagGraphQL\Schema\Mutation;

class MutationTest extends TestCase
{
    public function testGetNameUpsert()
    {
        $mutation = new Mutation(Mutation::ACTION_UPSERT, 'test');

        static::assertEquals('upsert_test', $mutation->getName());
    }

    public function testGetNameDelete()
    {
        $mutation = new Mutation(Mutation::ACTION_DELETE, 'test');

        static::assertEquals('delete_test', $mutation->getName());
    }

    public function testFromNameUpsert()
    {
        $mutation = Mutation::fromName('upsert_test');

        static::assertEquals(Mutation::ACTION_UPSERT, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }

    public function testFromNameDelete()
    {
        $mutation = Mutation::fromName('delete_test');

        static::assertEquals(Mutation::ACTION_DELETE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }
}