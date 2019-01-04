<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Schema;

use PHPUnit\Framework\TestCase;
use SwagGraphQL\Schema\Mutation;

class MutationTest extends TestCase
{
    public function testGetNameCreate()
    {
        $mutation = new Mutation(Mutation::ACTION_CREATE, 'test');

        static::assertEquals('create_test', $mutation->getName());
    }

    public function testGetNameUpdate()
    {
        $mutation = new Mutation(Mutation::ACTION_UPDATE, 'test');

        static::assertEquals('update_test', $mutation->getName());
    }

    public function testGetNameDelete()
    {
        $mutation = new Mutation(Mutation::ACTION_DELETE, 'test');

        static::assertEquals('delete_test', $mutation->getName());
    }

    public function testFromNameCreate()
    {
        $mutation = Mutation::fromName('create_test');

        static::assertEquals(Mutation::ACTION_CREATE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }

    public function testFromNameUpdate()
    {
        $mutation = Mutation::fromName('update_test');

        static::assertEquals(Mutation::ACTION_UPDATE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }

    public function testFromNameDelete()
    {
        $mutation = Mutation::fromName('delete_test');

        static::assertEquals(Mutation::ACTION_DELETE, $mutation->getAction());
        static::assertEquals('test', $mutation->getEntityName());
    }
}